<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\NewDeliveryAssigned;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Rider;
use App\Events\MajorDelayNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,received,at_sorting_center,in_transit,sorted,in_hub,assigned_to_rider,out_for_delivery,delivered,in_return_queue,damaged,flagged'],
            'hub_id' => ['nullable', 'integer', 'exists:hubs,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = Order::with(['hub', 'currentHub'])->latest()
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('awb_number', 'like', "%{$search}%")
                        ->orWhere('sender_name', 'like', "%{$search}%")
                        ->orWhere('recipient_name', 'like', "%{$search}%");
                });
            })
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['hub_id'] ?? null, fn ($query, $hubId) => $query->where('hub_id', $hubId))
            ->when($validated['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->paginate($validated['per_page'] ?? 10)
            ->withQueryString();

        return response()->json($orders);
    }

    public function receiveBulk(Request $request): JsonResponse
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'distinct', 'exists:orders,id'],
            'hub_id' => ['required', 'integer', 'exists:hubs,id'],
            'expected_count' => ['required', 'integer', 'min:1'],
        ]);

        $orders = DB::transaction(function () use ($validated, $request) {
            $orders = Order::whereIn('id', $validated['order_ids'])->lockForUpdate()->get();
            abort_if($orders->contains(fn (Order $order) => ! in_array($order->status, ['in_transit', 'received', 'pending'], true)), 422, 'Only parcels awaiting courier receipt can be received.');

            foreach ($orders as $order) {
                $order->update([
                    'current_hub_id' => $validated['hub_id'],
                    'status' => 'in_hub',
                    'hub_scanned_at' => now(),
                ]);
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => 'at_destination_hub',
                    'location_hub_id' => $validated['hub_id'],
                    'performed_by_user_id' => $request->user()->id,
                    'notes' => 'Courier bulk received. Expected '.$validated['expected_count'].' parcel(s); received batch contains '.$orders->count().'.',
                ]);
            }

            return $orders;
        });

        return response()->json([
            'message' => 'Courier bulk receipt recorded.',
            'expected_count' => $validated['expected_count'],
            'received_count' => $orders->count(),
            'discrepancy' => $validated['expected_count'] - $orders->count(),
            'orders' => $orders->load(['hub', 'currentHub']),
        ]);
    }

    public function intake(Request $request): JsonResponse
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $validated = $request->validate([
            'awb_number' => ['required', 'string', 'max:255'],
            'hub_id' => ['required', 'integer', 'exists:hubs,id'],
        ]);

        $order = DB::transaction(function () use ($validated) {
            $order = Order::where('awb_number', $validated['awb_number'])
                ->lockForUpdate()
                ->first();

            if (! $order) {
                abort(404, 'AWB number was not found.');
            }
            if ($order->status !== 'pending') {
                abort(422, 'This order has already been processed.');
            }

            $order->update([
                'hub_id' => $validated['hub_id'],
                'current_hub_id' => $validated['hub_id'],
                'status' => 'received',
                'scanned_at' => now(),
            ]);
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'at_sorting_center',
                'location_hub_id' => $validated['hub_id'],
                'performed_by_user_id' => request()->user()->id,
                'notes' => 'Parcel arrival scanned at sorting center.',
            ]);

            return $order;
        });

        return response()->json(['message' => 'Order received successfully.', 'order' => $order->load('hub')]);
    }

    public function scanLookup(Request $request, string $awbNumber): JsonResponse
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $order = Order::with(['hub', 'currentHub', 'rider.user', 'routePlan', 'binAssignments.bin.targetHub', 'statusHistories'])
            ->where('awb_number', $awbNumber)
            ->firstOrFail();

        return response()->json(['order' => $order]);
    }

    public function confirmArrivals(Request $request): JsonResponse
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
            'hub_id' => ['required', 'integer', 'exists:hubs,id'],
        ]);

        $count = DB::transaction(function () use ($validated) {
            return Order::whereIn('id', $validated['order_ids'])
                ->where('status', 'pending')
                ->update([
                    'hub_id' => $validated['hub_id'],
                    'current_hub_id' => $validated['hub_id'],
                    'status' => 'received',
                    'scanned_at' => now(),
                ]);
        });

        return response()->json(['message' => $count.' package(s) confirmed as arrived.', 'confirmed_count' => $count]);
    }

    public function transferScan(Request $request): JsonResponse
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $validated = $request->validate([
            'awb_number' => ['required', 'string', 'max:255'],
            'hub_id' => ['required', 'integer', 'exists:hubs,id'],
        ]);

        $order = DB::transaction(function () use ($validated, $request) {
            $order = Order::where('awb_number', $validated['awb_number'])->lockForUpdate()->firstOrFail();
            $order->update([
                'current_hub_id' => $validated['hub_id'],
                'status' => 'in_transit',
                'hub_scanned_at' => now(),
            ]);
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'in_transit',
                'location_hub_id' => $validated['hub_id'],
                'performed_by_user_id' => $request->user()->id,
                'notes' => 'Inter-facility transfer scanned.',
            ]);
            return $order;
        });

        return response()->json(['message' => 'Inter-facility transfer recorded.', 'order' => $order->fresh()->load(['hub', 'currentHub', 'statusHistories'])]);
    }

    public function handoverToRider(Request $request): JsonResponse
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $validated = $request->validate([
            'awb_number' => ['required', 'string', 'max:255'],
            'rider_id' => ['required', 'integer', 'exists:riders,id'],
        ]);

        $order = DB::transaction(function () use ($validated, $request) {
            $order = Order::where('awb_number', $validated['awb_number'])->lockForUpdate()->firstOrFail();
            $rider = Rider::with('user')->lockForUpdate()->findOrFail($validated['rider_id']);

            abort_if($rider->application_status !== 'approved', 422, 'Only an approved rider can receive parcels.');
            abort_if($rider->status === 'suspended', 422, 'The rider is currently deactivated.');
            abort_if($rider->hub_id !== ($order->current_hub_id ?: $order->hub_id), 422, 'The parcel is not at the rider\'s assigned hub.');
            abort_if($order->rider_id, 422, 'This parcel is already assigned to a rider.');
            abort_if(! in_array($order->status, ['received', 'in_hub']), 422, 'This parcel is not ready for rider handover.');

            $order->update([
                'rider_id' => $rider->id,
                'status' => 'in_hub',
                'delivery_status' => 'assigned',
                'assigned_at' => now(),
            ]);
            $rider->update(['status' => 'on_delivery']);
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'assigned_to_rider',
                'location_hub_id' => $order->current_hub_id ?: $order->hub_id,
                'performed_by_user_id' => $request->user()->id,
                'notes' => 'Parcel handed over to '.$rider->user->name.'.',
            ]);
            try {
                broadcast(new NewDeliveryAssigned($order->fresh()->load('hub')));
            } catch (\Throwable) {
                // Realtime delivery notification is optional for API clients.
            }

            return $order;
        });

        return response()->json(['message' => 'Parcel handed over to rider.', 'order' => $order->fresh()->load(['hub', 'currentHub', 'rider.user', 'statusHistories'])]);
    }

    public function updateOperationalStatus(Request $request, Order $order): JsonResponse
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $validated = $request->validate([
            'status' => ['required', 'in:received,in_transit,in_hub,out_for_delivery,delivered,delivery_failed,in_return_queue'],
            'reason' => ['required_if:status,delivery_failed', 'nullable', 'string', 'max:1000'],
        ]);

        $nextStatus = $validated['status'] === 'delivery_failed' ? 'in_return_queue' : $validated['status'];
        $order->update([
            'status' => $nextStatus,
            'delivery_status' => $validated['status'] === 'delivery_failed' ? 'failed' : $validated['status'],
            'delivery_failure_reason' => $validated['reason'] ?? null,
            'delivered_at' => $validated['status'] === 'delivered' ? now() : $order->delivered_at,
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $validated['status'],
            'location_hub_id' => $order->current_hub_id ?: $order->hub_id,
            'performed_by_user_id' => $request->user()->id,
            'notes' => $validated['reason'] ?? 'Status updated by logistics staff.',
        ]);

        return response()->json([
            'message' => 'Parcel status updated.',
            'order' => $order->fresh()->load(['hub', 'currentHub', 'rider.user', 'statusHistories']),
        ]);
    }

    public function override(Request $request, Order $order): JsonResponse
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $validated = $request->validate([
            'status' => ['required', 'in:damaged,flagged'],
            'reason_code' => ['required', 'string', 'max:100'],
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $order->update([
            'status' => $validated['status'],
            'flag_reason' => $validated['reason_code'].': '.$validated['notes'],
        ]);
        try { broadcast(new MajorDelayNotification($order, $validated['reason_code'].': '.$validated['notes'])); } catch (\Throwable) { }

        return response()->json(['message' => 'Order override saved.', 'order' => $order->load('hub')]);
    }
}