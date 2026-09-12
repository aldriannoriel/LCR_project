<?php

namespace App\Http\Controllers\Api;

use App\Events\NewDeliveryAssigned;
use App\Events\NewPickupAssigned;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PickupRequest;
use App\Models\Rider;
use App\Models\RiderPerformance;
use App\Models\ReturnRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CourierController extends Controller
{
    // ─── Dashboard ──────────────────────────────────────────────────────────────

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $rider = $user->rider;

        if (! $rider) {
            return response()->json(['message' => 'No rider profile found.'], 404);
        }

        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();

        // Pickup stats
        $pendingPickups = PickupRequest::where('assigned_rider_id', $rider->id)
            ->whereIn('status', ['pending', 'verified', 'assigned'])
            ->count();

        $completedPickups = PickupRequest::where('assigned_rider_id', $rider->id)
            ->where('status', 'completed')
            ->whereDate('completed_at', '>=', $today)
            ->count();

        // Delivery stats
        $todayDeliveries = Order::where('rider_id', $rider->id)
            ->whereDate('assigned_at', '>=', $today)
            ->count();

        $completedDeliveries = Order::where('rider_id', $rider->id)
            ->where('status', 'delivered')
            ->whereDate('assigned_at', '>=', $today)
            ->count();

        $failedDeliveries = Order::where('rider_id', $rider->id)
            ->where('delivery_status', 'failed')
            ->whereDate('assigned_at', '>=', $today)
            ->count();

        // Performance
        $performance = $rider->performance;
        $weeklyEarnings = $performance ? $performance->total_earnings ?? 0 : 0;

        return response()->json([
            'pending_pickups' => $pendingPickups,
            'completed_pickups_today' => $completedPickups,
            'today_deliveries' => $todayDeliveries,
            'completed_deliveries_today' => $completedDeliveries,
            'failed_deliveries_today' => $failedDeliveries,
            'weekly_earnings' => $weeklyEarnings,
            'success_rate' => $performance ? round(($performance->successful_deliveries / max($performance->total_deliveries, 1)) * 100, 1) : 100,
            'rider_status' => $rider->status,
        ]);
    }

    // ─── Pickups ────────────────────────────────────────────────────────────────

    public function pickups(Request $request)
    {
        $rider = $request->user()->rider;
        if (! $rider) {
            return response()->json(['message' => 'No rider profile found.'], 404);
        }

        $status = $request->query('status', 'all');

        $query = PickupRequest::with(['seller', 'hub'])
            ->where('assigned_rider_id', $rider->id)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('scheduled_date');

        return response()->json($query->paginate($request->integer('per_page', 10)));
    }

    public function pickupDetail(Request $request, PickupRequest $pickup)
    {
        $rider = $this->riderOrFail($request);

        if ($pickup->assigned_rider_id !== $rider->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($pickup->load(['seller', 'hub']));
    }

    public function startPickup(Request $request, PickupRequest $pickup)
    {
        $rider = $this->riderOrFail($request);

        if ($pickup->assigned_rider_id !== $rider->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if (! in_array($pickup->status, ['assigned'])) {
            return response()->json(['message' => 'Pickup cannot be started in current status.'], 422);
        }

        $pickup->update(['status' => 'in_progress']);

        return response()->json([
            'message' => 'Pickup started.',
            'pickup' => $pickup->fresh()->load(['seller', 'hub']),
        ]);
    }

    public function completePickup(Request $request, PickupRequest $pickup)
    {
        $rider = $this->riderOrFail($request);

        if ($pickup->assigned_rider_id !== $rider->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        if ($pickup->status !== 'in_progress') {
            return response()->json(['message' => 'Pickup is not currently in progress.'], 422);
        }

        $validated = $request->validate([
            'actual_parcels_collected' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($pickup, $validated) {
            $pickup->update([
                'status' => 'completed',
                'completed_at' => now(),
                'actual_parcels_collected' => $validated['actual_parcels_collected'],
            ]);

            // Create inbound orders
            $count = $validated['actual_parcels_collected'];
            for ($i = 1; $i <= $count; $i++) {
                $awb = 'LCR-' . date('Y') . '-' . strtoupper(substr(md5($pickup->id . $i . now()), 0, 8));
                Order::create([
                    'awb_number' => $awb,
                    'hub_id' => $pickup->hub_id,
                    'current_hub_id' => $pickup->hub_id,
                    'status' => 'received',
                    'sender_name' => $pickup->contact_person,
                    'recipient_name' => 'Recipient for ' . $awb,
                    'recipient_address' => 'Customer Destination Address, Philippines',
                    'weight_kg' => 1.0,
                    'scanned_at' => now(),
                ]);
            }

            // Update performance
            if ($rider = $pickup->rider) {
                $rider->performance()->updateOrCreate(['rider_id' => $rider->id], [])
                    ->increment('successful_pickups', 1);
            }
        });

        return response()->json([
            'message' => 'Pickup completed. ' . $validated['actual_parcels_collected'] . ' parcels added to inbound queue.',
            'pickup' => $pickup->fresh()->load(['seller', 'hub']),
        ]);
    }

    // ─── Deliveries ─────────────────────────────────────────────────────────────

    public function deliveries(Request $request)
    {
        $rider = $request->user()->rider;
        if (! $rider) {
            return response()->json(['message' => 'No rider profile found.'], 404);
        }

        $status = $request->query('status', 'all');
        $date = $request->query('date');

        $query = Order::with(['hub'])
            ->where('rider_id', $rider->id)
            ->when($status !== 'all', function ($q) use ($status) {
                match ($status) {
                    'pending' => $q->whereIn('status', ['in_hub', 'out_for_delivery']),
                    'completed' => $q->where('status', 'delivered'),
                    'failed' => $q->whereIn('delivery_status', ['failed', 'returned']),
                    default => $q,
                };
            })
            ->when($date, fn ($q, $d) => $q->whereDate('assigned_at', $d))
            ->orderByDesc('assigned_at');

        return response()->json($query->paginate($request->integer('per_page', 15)));
    }

    public function deliveryDetail(Request $request, Order $order)
    {
        $rider = $this->riderOrFail($request);

        if ($order->rider_id !== $rider->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($order->load(['hub', 'statusHistories']));
    }

    public function startDelivery(Request $request, Order $order)
    {
        $rider = $this->riderOrFail($request);

        if ($order->rider_id !== $rider->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($order->status !== 'in_hub') {
            return response()->json(['message' => 'Parcel is not ready for pickup from the sorting center.'], 422);
        }

        $order->update([
            'status' => 'out_for_delivery',
            'delivery_status' => 'out_for_delivery',
            'dispatched_at' => now(),
        ]);

        return response()->json([
            'message' => 'Parcel picked up from the sorting center and marked out for delivery.',
            'order' => $order->fresh()->load(['hub', 'statusHistories']),
        ]);
    }

    public function completeDelivery(Request $request, Order $order)
    {
        $rider = $this->riderOrFail($request);

        if ($order->rider_id !== $rider->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        if ($order->status !== 'out_for_delivery') {
            return response()->json(['message' => 'Delivery is not currently out for delivery.'], 422);
        }

        $validated = $request->validate([
            'proof_image' => 'nullable|image|max:5120',
            'recipient_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($order, $validated, $rider) {
            $order->update([
                'status' => 'delivered',
                'delivery_status' => 'delivered',
                'delivered_at' => now(),
                'recipient_name' => $validated['recipient_name'] ?? $order->recipient_name,
                'delivery_notes' => $validated['notes'] ?? null,
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'delivered',
                'notes' => 'Delivered by rider' . ($validated['notes'] ? ': ' . $validated['notes'] : ''),
            ]);

            // Update performance & earnings
            $perf = $rider->performance()->updateOrCreate(['rider_id' => $rider->id], []);
            $perf->increment('successful_deliveries', 1);
            $perf->increment('total_deliveries', 1);
            $perf->increment('total_earnings', $order->delivery_fee ?? 50);

            $rider->update(['status' => 'available']);
        });

        return response()->json([
            'message' => 'Delivery marked as completed.',
            'order' => $order->fresh()->load(['hub', 'statusHistories']),
        ]);
    }

    public function failedDelivery(Request $request, Order $order)
    {
        $rider = $this->riderOrFail($request);

        if ($order->rider_id !== $rider->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        if ($order->status !== 'out_for_delivery') {
            return response()->json(['message' => 'Delivery is not currently out for delivery.'], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|in:refused,unreachable,wrong_address,damaged,other',
            'notes' => 'nullable|string|max:500',
            'photo' => 'nullable|image|max:5120',
        ]);

        DB::transaction(function () use ($order, $validated, $rider) {
            $order->update([
                'status' => 'in_return_queue',
                'delivery_status' => 'failed',
                'delivery_failure_reason' => $validated['reason'] . ($validated['notes'] ? ': ' . $validated['notes'] : ''),
            ]);

            ReturnRecord::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'return_reason' => match ($validated['reason']) {
                        'refused' => 'customer_refused',
                        'wrong_address' => 'incorrect_address',
                        'damaged' => 'damaged_goods',
                        default => 'failed_delivery_3x',
                    },
                    'status' => 'pending_intake',
                    'action_notes' => $validated['notes'] ?? null,
                ]
            );

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'delivery_failed',
                'notes' => 'Failed: ' . $validated['reason'] . ($validated['notes'] ? ' - ' . $validated['notes'] : ''),
            ]);

            // Update performance
            $perf = $rider->performance()->updateOrCreate(['rider_id' => $rider->id], []);
            $perf->increment('failed_deliveries', 1);
            $perf->increment('total_deliveries', 1);

            $rider->update(['status' => 'available']);
        });

        return response()->json([
            'message' => 'Delivery marked as failed. Package will be returned.',
            'order' => $order->fresh()->load(['hub', 'statusHistories']),
        ]);
    }

    // ─── Earnings ──────────────────────────────────────────────────────────────

    public function earnings(Request $request)
    {
        $rider = $request->user()->rider;
        if (! $rider) {
            return response()->json(['message' => 'No rider profile found.'], 404);
        }

        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());

        $performance = $rider->performance;

        // Calculate earnings for date range
        $completedCount = Order::where('rider_id', $rider->id)
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$from, $to . ' 23:59:59'])
            ->count();

        $failedCount = Order::where('rider_id', $rider->id)
            ->whereIn('delivery_status', ['failed', 'returned'])
            ->whereBetween('assigned_at', [$from, $to . ' 23:59:59'])
            ->count();

        $pickupCount = PickupRequest::where('assigned_rider_id', $rider->id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from, $to . ' 23:59:59'])
            ->count();

        // Estimate earnings (base rate + per delivery)
        $deliveryEarnings = $completedCount * ($performance->delivery_rate ?? 50);
        $pickupEarnings = $pickupCount * ($performance->pickup_rate ?? 30);
        $totalEarnings = $deliveryEarnings + $pickupEarnings;

        // Weekly breakdown
        $weeklyBreakdown = [];
        for ($i = 0; $i < 4; $i++) {
            $weekStart = now()->startOfWeek()->subWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek();

            $weekCompleted = Order::where('rider_id', $rider->id)
                ->where('status', 'delivered')
                ->whereBetween('delivered_at', [$weekStart, $weekEnd])
                ->count();

            $weeklyBreakdown[] = [
                'week' => $weekStart->format('M d'),
                'deliveries' => $weekCompleted,
                'earnings' => $weekCompleted * ($performance->delivery_rate ?? 50),
            ];
        }

        return response()->json([
            'period' => ['from' => $from, 'to' => $to],
            'summary' => [
                'completed_deliveries' => $completedCount,
                'failed_deliveries' => $failedCount,
                'completed_pickups' => $pickupCount,
                'delivery_rate' => $performance->delivery_rate ?? 50,
                'pickup_rate' => $performance->pickup_rate ?? 30,
                'total_earnings' => $totalEarnings,
            ],
            'weekly_breakdown' => $weeklyBreakdown,
        ]);
    }

    // ─── History ────────────────────────────────────────────────────────────────

    public function history(Request $request)
    {
        $rider = $request->user()->rider;
        if (! $rider) {
            return response()->json(['message' => 'No rider profile found.'], 404);
        }

        $type = $request->query('type', 'all'); // 'deliveries', 'pickups', 'all'
        $from = $request->query('from');
        $to = $request->query('to');

        $ordersQuery = Order::where('rider_id', $rider->id)
            ->when($from, fn ($q) => $q->whereDate('assigned_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('assigned_at', '<=', $to));

        $pickupsQuery = PickupRequest::where('assigned_rider_id', $rider->id)
            ->where('status', 'completed')
            ->when($from, fn ($q) => $q->whereDate('completed_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('completed_at', '<=', $to));

        $orders = $type !== 'pickups' ? $ordersQuery->get()->map(fn ($o) => [
            'type' => 'delivery',
            'id' => $o->id,
            'awb' => $o->awb_number,
            'recipient' => $o->recipient_name,
            'status' => $o->status,
            'date' => $o->assigned_at,
            'completed_at' => $o->delivered_at,
        ]) : collect();

        $pickups = $type !== 'deliveries' ? $pickupsQuery->get()->map(fn ($p) => [
            'type' => 'pickup',
            'id' => $p->id,
            'code' => $p->request_code,
            'seller' => $p->contact_person,
            'status' => $p->status,
            'date' => $p->scheduled_date,
            'completed_at' => $p->completed_at,
            'parcels' => $p->actual_parcels_collected,
        ]) : collect();

        $history = $orders->merge($pickups)->sortByDesc('completed_at')->values();

        return response()->json(['data' => $history->forPage(1, 50)]);
    }

    // ─── Status Toggle ─────────────────────────────────────────────────────────

    public function toggleStatus(Request $request)
    {
        $rider = $request->user()->rider;
        if (! $rider) {
            return response()->json(['message' => 'No rider profile found.'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:available,off_duty',
        ]);

        if ($rider->status === 'suspended') {
            return response()->json(['message' => 'Account is suspended. Contact support.'], 403);
        }

        $rider->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Status updated.',
            'status' => $rider->status,
        ]);
    }

    // ─── Profile ────────────────────────────────────────────────────────────────

    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => $user->load(['roles', 'hub']),
            'rider' => $user->rider?->load('performance'),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|digits:11',
        ]);

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'phone_number' => $validated['phone_number'],
        ]);

        if ($user->rider) {
            $user->rider->update([
                'phone_number' => $validated['phone_number'],
            ]);
        }

        return response()->json([
            'message' => 'Profile updated.',
            'user' => $user->fresh()->load(['roles', 'hub']),
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json(['message' => 'Password updated.']);
    }

    private function riderOrFail(Request $request): Rider
    {
        $rider = $request->user()->rider;

        abort_if(! $rider, 404, 'No rider profile found.');

        return $rider;
    }
}
