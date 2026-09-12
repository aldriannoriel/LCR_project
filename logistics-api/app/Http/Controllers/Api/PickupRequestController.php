<?php

namespace App\Http\Controllers\Api;

use App\Events\NewPickupAssigned;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PickupRequest;
use App\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PickupRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status');
        $search = $request->query('search');
        $hubId = $request->query('hub_id');

        $query = PickupRequest::with(['seller', 'hub', 'rider.user', 'verifiedBy'])
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($hubId, fn ($q, $id) => $q->where('hub_id', $id))
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('request_code', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhere('city_municipality', 'like', "%{$search}%")
                        ->orWhereHas('seller', fn ($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('business_name', 'like', "%{$search}%"));
                });
            })
            // If user is a regular seller/customer without admin/manager roles, show only their pickups
            ->when(! $user->hasAnyRole(['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']), function ($q) use ($user) {
                $q->where('seller_id', $user->id);
            })
            ->latest('id');

        return response()->json($query->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_person' => 'required|string|max:255',
            'contact_number' => 'required|string|max:30',
            'province' => 'required|string|max:255',
            'city_municipality' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'pickup_address' => 'required|string|max:500',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required|string|in:morning,afternoon,evening',
            'estimated_parcels' => 'required|integer|min:1|max:1000',
            'package_type' => 'nullable|string',
            'special_instructions' => 'nullable|string|max:1000',
            'hub_id' => 'nullable|exists:hubs,id',
        ]);

        $requestCode = 'PKP-' . date('Y') . '-' . strtoupper(Str::random(6));

        $pickup = PickupRequest::create([
            'request_code' => $requestCode,
            'seller_id' => $request->user()->id,
            'hub_id' => $validated['hub_id'] ?? $request->user()->hub_id,
            'contact_person' => $validated['contact_person'],
            'contact_number' => $validated['contact_number'],
            'province' => $validated['province'],
            'city_municipality' => $validated['city_municipality'],
            'barangay' => $validated['barangay'],
            'pickup_address' => $validated['pickup_address'],
            'scheduled_date' => $validated['scheduled_date'],
            'time_slot' => $validated['time_slot'],
            'estimated_parcels' => $validated['estimated_parcels'],
            'package_type' => $validated['package_type'] ?? 'parcels',
            'special_instructions' => $validated['special_instructions'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json($pickup->load(['seller', 'hub']), 201);
    }

    public function show(PickupRequest $pickupRequest)
    {
        $user = request()->user();
        abort_unless(
            $pickupRequest->seller_id === $user->id || $user->hasAnyRole(['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']),
            403,
            'You are not authorized to view this pickup request.'
        );

        return response()->json($pickupRequest->load(['seller', 'hub', 'rider.user', 'verifiedBy']));
    }

    public function verify(Request $request, PickupRequest $pickupRequest)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);
        abort_unless($pickupRequest->status === 'pending', 422, 'Only pending pickup requests can be verified.');

        $validated = $request->validate([
            'hub_id' => 'nullable|exists:hubs,id',
            'verification_notes' => 'nullable|string|max:1000',
        ]);

        $pickupRequest->update([
            'status' => 'verified',
            'hub_id' => $validated['hub_id'] ?? $pickupRequest->hub_id ?? $request->user()->hub_id,
            'verified_by_user_id' => $request->user()->id,
            'verified_at' => now(),
            'verification_notes' => $validated['verification_notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Pickup request verified and approved for rider dispatch.',
            'pickup' => $pickupRequest->fresh()->load(['seller', 'hub', 'verifiedBy', 'rider.user']),
        ]);
    }

    public function assignRider(Request $request, PickupRequest $pickupRequest)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);
        abort_unless($pickupRequest->status === 'verified', 422, 'Only verified pickup requests can be assigned.');

        $validated = $request->validate([
            'rider_id' => 'required|exists:riders,id',
        ]);

        $rider = Rider::findOrFail($validated['rider_id']);

        $pickupRequest->update([
            'assigned_rider_id' => $rider->id,
            'status' => 'assigned',
        ]);

        try {
            broadcast(new NewPickupAssigned($pickupRequest->fresh()->load(['seller', 'hub', 'rider.user'])))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => "Assigned pickup to rider {$rider->user->name}.",
            'pickup' => $pickupRequest->fresh()->load(['seller', 'hub', 'rider.user']),
        ]);
    }

    public function complete(Request $request, PickupRequest $pickupRequest)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);
        abort_unless(in_array($pickupRequest->status, ['assigned', 'in_progress'], true), 422, 'This pickup is not ready to be completed.');

        $validated = $request->validate([
            'actual_parcels_collected' => 'required|integer|min:1',
            'create_inbound_parcels' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($pickupRequest, $validated, $request) {
            $pickupRequest->update([
                'status' => 'completed',
                'completed_at' => now(),
                'actual_parcels_collected' => $validated['actual_parcels_collected'],
            ]);

            // If requested, generate inbound orders into the hub
            if (! empty($validated['create_inbound_parcels']) && $pickupRequest->hub_id) {
                $count = $validated['actual_parcels_collected'];
                for ($i = 1; $i <= $count; $i++) {
                    $awb = 'LCR-' . date('Y') . '-' . strtoupper(Str::random(8));
                    Order::create([
                        'awb_number' => $awb,
                        'hub_id' => $pickupRequest->hub_id,
                        'current_hub_id' => $pickupRequest->hub_id,
                        'status' => 'received',
                        'sender_name' => $pickupRequest->contact_person,
                        'recipient_name' => 'Recipient for ' . $awb,
                        'recipient_address' => 'Customer Destination Address, Philippines',
                        'weight_kg' => 1.0,
                        'scanned_at' => now(),
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Pickup marked as completed successfully.',
            'pickup' => $pickupRequest->fresh()->load(['seller', 'hub', 'rider.user']),
        ]);
    }

    public function cancel(Request $request, PickupRequest $pickupRequest)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);
        abort_unless(! in_array($pickupRequest->status, ['completed', 'cancelled'], true), 422, 'This pickup can no longer be cancelled.');

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $pickupRequest->update([
            'status' => 'cancelled',
            'verification_notes' => 'Cancelled: ' . $validated['reason'],
        ]);

        return response()->json([
            'message' => 'Pickup request has been cancelled.',
            'pickup' => $pickupRequest->fresh(),
        ]);
    }
}
