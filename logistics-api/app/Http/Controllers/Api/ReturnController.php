<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ReturnRecord;
use App\Events\MajorDelayNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = ReturnRecord::with(['order.currentHub', 'order.hub', 'actionTaker'])->latest()
            ->when($request->reason, fn ($q, $value) => $q->where('return_reason', $value))
            ->when($request->status, fn ($q, $value) => $q->where('status', $value))
            ->when($request->hub_id, fn ($q, $value) => $q->whereHas('order', fn ($q) => $q->where('current_hub_id', $value)->orWhere('hub_id', $value)))
            ->when($request->search, fn ($q, $value) => $q->whereHas('order', fn ($q) => $q->where('awb_number', 'like', "%{$value}%")->orWhere('recipient_name', 'like', "%{$value}%")));
        return response()->json($query->paginate($request->integer('per_page', 15)));
    }

    public function intake(Request $request)
    {
        $data = $request->validate(['awb_number' => ['required', 'string'], 'return_reason' => ['required', 'in:failed_delivery_3x,customer_refused,incorrect_address,damaged_goods,expired_holding'], 'notes' => ['nullable', 'string']]);
        $return = DB::transaction(function () use ($data, $request) {
            $order = Order::where('awb_number', $data['awb_number'])->lockForUpdate()->firstOrFail();
            $return = ReturnRecord::firstOrCreate(['order_id' => $order->id], ['return_reason' => $data['return_reason'], 'status' => 'in_reverse_queue', 'action_notes' => $data['notes'] ?? null]);
            $order->update(['status' => 'in_return_queue']);
            OrderStatusHistory::create(['order_id' => $order->id, 'status' => 'in_return_queue', 'location_hub_id' => $order->current_hub_id ?? $order->hub_id, 'performed_by_user_id' => $request->user()->id, 'notes' => $data['notes'] ?? $data['return_reason']]);
            return $return;
        });
        return response()->json($return->load('order'), 201);
    }

    public function action(Request $request, ReturnRecord $return)
    {
        $data = $request->validate(['action' => ['required', 'in:reattempt,rts,dispose'], 'reason_code' => ['required', 'string'], 'notes' => ['required', 'string']]);
        if ($data['action'] === 'dispose' && blank($data['reason_code'])) abort(422, 'A disposal reason is required.');
        $updated = DB::transaction(function () use ($data, $request, $return) {
            $order = $return->order()->lockForUpdate()->first();
            $status = ['reattempt' => 'reattempt_scheduled', 'rts' => 'rts_in_transit', 'dispose' => 'disposed'][$data['action']];
            $orderStatus = ['reattempt' => 'pending', 'rts' => 'rts', 'dispose' => 'disposed'][$data['action']];
            $return->update(['status' => $status, 'action_taken_by' => $request->user()->id, 'action_notes' => $data['reason_code'].': '.$data['notes']]);
            $order->update(['status' => $orderStatus]);
            if ($data['action'] === 'rts' && $order->routePlan) $order->routePlan->update(['regional_hub_id' => $order->hub_id, 'route_path_json' => [['id' => $order->hub_id, 'name' => $order->hub->name, 'type' => $order->hub->type]]]);
            OrderStatusHistory::create(['order_id' => $order->id, 'status' => $orderStatus, 'location_hub_id' => $order->current_hub_id ?? $order->hub_id, 'performed_by_user_id' => $request->user()->id, 'notes' => $data['reason_code'].': '.$data['notes']]);
            return $return;
        });
        if ($data['action'] !== 'reattempt') {
            try { broadcast(new MajorDelayNotification($updated->order, $data['reason_code'].': '.$data['notes'])); } catch (\Throwable) { }
        }
        return response()->json($updated->load('order'));
    }

    public function history(Order $order)
    {
        return response()->json($order->statusHistories()->with(['hub', 'performer'])->oldest()->get());
    }
}