<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hub;
use App\Models\Order;
use App\Models\TransferRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\TransferDispatchNotification;

class HubController extends Controller
{
    public function grid(Request $request)
    {
        return response()->json(Hub::whereIn('code', ['HUB-LUZ-CTR', 'HUB-LUZ-NL', 'HUB-LUZ-SL', 'HUB-VIS-GW', 'HUB-VIS-CEB', 'HUB-VIS-ILO', 'HUB-MIN-CG', 'HUB-MIN-DVO', 'HUB-MIN-CDO'])->withCount(['inboundOrders as active_orders_count' => fn ($q) => $q->whereIn('status', ['received', 'in_hub', 'in_transit'])])->when($request->archipelago_id, fn ($q, $id) => $q->where('archipelago_id', $id))->when($request->hub_type, fn ($q, $type) => $q->where('hub_type', $type))->get()->map(function (Hub $hub) { $hub->hub_type ??= ['national_sorting' => 'national', 'gateway' => 'gateway', 'regional' => 'regional'][$hub->type] ?? 'regional'; $hub->current_stock = $hub->current_stock ?? Order::where('current_hub_id', $hub->id)->where('status', 'in_hub')->count(); $hub->utilization_percentage = $hub->capacity > 0 ? round(($hub->current_stock / $hub->capacity) * 100, 1) : 0; return $hub; }));
    }

    public function inventory(Request $request, Hub $hub)
    {
        $query = Order::with('routePlan')->where('current_hub_id', $hub->id)->where('status', 'in_hub')->when($request->search, fn ($q, $search) => $q->where(fn ($q) => $q->where('awb_number', 'like', "%{$search}%")->orWhere('sender_name', 'like', "%{$search}%")->orWhere('recipient_name', 'like', "%{$search}%")))->when($request->date_scanned, fn ($q, $date) => $q->whereDate('hub_scanned_at', $date));
        return response()->json($query->latest('hub_scanned_at')->paginate($request->integer('per_page', 15)));
    }

    public function requests(Request $request)
    {
        return response()->json(TransferRequest::with(['fromHub', 'toHub', 'requester', 'items'])->when($request->status, fn ($q, $v) => $q->where('status', $v))->when($request->from_hub_id, fn ($q, $v) => $q->where('from_hub_id', $v))->when($request->to_hub_id, fn ($q, $v) => $q->where('to_hub_id', $v))->latest()->paginate($request->integer('per_page', 15)));
    }

    public function createRequest(Request $request)
    {
        $data = $request->validate(['from_hub_id' => ['required', 'exists:hubs,id'], 'to_hub_id' => ['required', 'exists:hubs,id', 'different:from_hub_id'], 'order_ids' => ['required', 'array', 'min:1'], 'order_ids.*' => ['integer', 'exists:orders,id'], 'notes' => ['nullable', 'string']]);
        $transfer = DB::transaction(function () use ($data, $request) { $transfer = TransferRequest::create(['reference_number' => 'TR-'.now()->format('Y').'-'.str_pad((string) (TransferRequest::max('id') + 1), 4, '0', STR_PAD_LEFT), 'from_hub_id' => $data['from_hub_id'], 'to_hub_id' => $data['to_hub_id'], 'requested_by_user_id' => $request->user()->id, 'notes' => $data['notes'] ?? null]); $orders = Order::whereIn('id', $data['order_ids'])->where('current_hub_id', $data['from_hub_id'])->where('status', 'in_hub')->lockForUpdate()->get(); abort_if($orders->count() !== count($data['order_ids']), 422, 'Some selected orders are no longer available in this hub.'); $transfer->items()->attach($orders->modelKeys()); return $transfer; });
        return response()->json($transfer->load(['fromHub', 'toHub', 'items']), 201);
    }

    public function status(Request $request, TransferRequest $transferRequest)
    {
        $data = $request->validate(['status' => ['required', 'in:approved,rejected,in_transit,completed']]);
        $updated = DB::transaction(function () use ($data, $request, $transferRequest) { $orders = $transferRequest->items()->get(); if ($data['status'] === 'approved' || $data['status'] === 'in_transit') $orders->each->update(['status' => 'in_transit']); if ($data['status'] === 'completed') { $orders->each->update(['current_hub_id' => $transferRequest->to_hub_id, 'status' => 'in_hub', 'hub_scanned_at' => now()]); Hub::whereKey($transferRequest->from_hub_id)->decrement('current_stock', $orders->count()); Hub::whereKey($transferRequest->to_hub_id)->increment('current_stock', $orders->count()); } $transferRequest->update(['status' => $data['status'], 'approved_by_user_id' => in_array($data['status'], ['approved', 'rejected']) ? $request->user()->id : $transferRequest->approved_by_user_id]); return $transferRequest; });
        if ($data['status'] === 'in_transit') { try { broadcast(new TransferDispatchNotification($updated->load(['fromHub', 'toHub']))); } catch (\Throwable) { } }
        return response()->json($updated->load(['fromHub', 'toHub', 'items']));
    }
}