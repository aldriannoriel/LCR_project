<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
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
            'status' => ['nullable', 'in:pending,received,in_transit,out_for_delivery,delivered,damaged,flagged'],
            'hub_id' => ['nullable', 'integer', 'exists:hubs,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = Order::with('hub')->latest()
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

            return $order;
        });

        return response()->json(['message' => 'Order received successfully.', 'order' => $order->load('hub')]);
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