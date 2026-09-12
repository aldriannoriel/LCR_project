<?php

namespace App\Http\Controllers\Api;

use App\Events\NewDeliveryAssigned;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rider;
use App\Models\RiderPerformance;
use App\Models\User;
use App\Models\CoverageArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RiderController extends Controller
{
    public function index(Request $request)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $validated = $request->validate([
            'search' => ['nullable', 'string'],
            'hub_id' => ['nullable', 'integer'],
            'archipelago_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:available,on_delivery,off_duty,suspended'],
            'application_status' => ['nullable', 'in:pending_review,approved,rejected,all'],
            'vehicle_type' => ['nullable', 'in:motorcycle,van,tricycle,truck'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json(
            Rider::with(['hub', 'coverageArea', 'user', 'performance'])
                ->when($validated['search'] ?? null, function ($q, $search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('plate_number', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%")
                            ->orWhere('license_number', 'like', "%{$search}%")
                            ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                    });
                })
                ->when($validated['hub_id'] ?? null, fn ($q, $id) => $q->where('hub_id', $id))
                ->when($validated['archipelago_id'] ?? null, fn ($q, $id) => $q->whereHas('hub', fn ($hq) => $hq->where('archipelago_id', $id)))
                ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
                ->when(
                    isset($validated['application_status']) && $validated['application_status'] !== 'all',
                    fn ($q) => $q->where('application_status', $validated['application_status'])
                )
                ->when($validated['vehicle_type'] ?? null, fn ($q, $type) => $q->where('vehicle_type', $type))
                ->latest()
                ->paginate($validated['per_page'] ?? 10)
        );
    }

    public function store(Request $request)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager']);

        $data = $this->validated($request);

        return DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['name'],
                'first_name' => $data['first_name'] ?? explode(' ', $data['name'])[0],
                'last_name' => $data['last_name'] ?? (explode(' ', $data['name'])[1] ?? 'Rider'),
                'email' => $data['email'],
                'password' => bcrypt($data['password'] ?? 'password123'),
                'hub_id' => $data['hub_id'],
                'approval_status' => 'approved',
            ]);

            $licenseDoc = $request->hasFile('license_doc')
                ? $request->file('license_doc')->store('documents', 'local')
                : null;

            $orCrDoc = $request->hasFile('vehicle_or_cr')
                ? $request->file('vehicle_or_cr')->store('documents', 'local')
                : null;

            $rider = Rider::create([
                'user_id' => $user->id,
                'hub_id' => $data['hub_id'],
                'coverage_area_id' => $data['coverage_area_id'] ?? null,
                'vehicle_type' => $data['vehicle_type'],
                'plate_number' => $data['plate_number'] ?? null,
                'license_number' => $data['license_number'] ?? null,
                'license_doc_path' => $licenseDoc,
                'vehicle_or_cr_path' => $orCrDoc,
                'status' => $data['status'] ?? 'available',
                'application_status' => $data['application_status'] ?? 'approved',
                'phone_number' => $data['phone_number'] ?? null,
            ]);

            RiderPerformance::create(['rider_id' => $rider->id]);

            return response()->json($rider->load(['user', 'hub', 'performance']), 201);
        });
    }

    public function update(Request $request, Rider $rider)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager']);

        $data = $this->validated($request, false);

        $rider->update(collect($data)->only([
            'hub_id', 'coverage_area_id', 'vehicle_type', 'plate_number', 'license_number', 'status', 'phone_number', 'application_status'
        ])->all());

        if (isset($data['name']) || isset($data['email'])) {
            $rider->user->update(collect($data)->only(['name', 'email'])->all());
        }

        return response()->json($rider->load(['user', 'hub', 'performance']));
    }

    public function status(Request $request, Rider $rider)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $data = $request->validate([
            'status' => ['required', 'in:available,on_delivery,off_duty,suspended'],
        ]);

        $rider->update($data);

        return response()->json($rider->load(['user', 'hub', 'performance']));
    }

    public function approveApplication(Request $request, Rider $rider)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager']);

        $rider->update([
            'application_status' => 'approved',
            'status' => 'available',
            'rejection_reason' => null,
        ]);

        return response()->json([
            'message' => "Rider {$rider->user->name}'s application has been approved.",
            'rider' => $rider->fresh()->load(['user', 'hub', 'performance']),
        ]);
    }

    public function rejectApplication(Request $request, Rider $rider)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager']);

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:1000',
        ]);

        $rider->update([
            'application_status' => 'rejected',
            'status' => 'suspended',
            'rejection_reason' => $validated['reason'],
        ]);

        return response()->json([
            'message' => "Rider {$rider->user->name}'s application has been disapproved.",
            'rider' => $rider->fresh()->load(['user', 'hub', 'performance']),
        ]);
    }

    public function toggleActive(Request $request, Rider $rider)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager']);

        $newStatus = $rider->status === 'suspended' ? 'available' : 'suspended';
        $rider->update(['status' => $newStatus]);

        return response()->json([
            'message' => $newStatus === 'available' ? 'Rider account activated.' : 'Rider account deactivated / suspended.',
            'rider' => $rider->fresh()->load(['user', 'hub', 'performance']),
        ]);
    }

    public function details(Rider $rider)
    {
        $this->requireAnyRole(request(), ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        return response()->json(
            $rider->load(['user', 'hub', 'performance', 'orders.routePlan'])
                ->load(['manifests' => fn ($query) => $query->whereIn('status', ['draft', 'dispatched'])])
                ->load('pickupRequests')
        );
    }

    public function assignOrders(Request $request)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $data = $request->validate([
            'rider_id' => ['required', 'exists:riders,id'],
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
        ]);

        $rider = Rider::with('coverageArea')->findOrFail($data['rider_id']);
        $limit = ['motorcycle' => 30, 'tricycle' => 50, 'van' => 100, 'truck' => 300][$rider->vehicle_type];
        abort_if(count($data['order_ids']) > $limit, 422, "This vehicle can carry at most {$limit} packages.");

        $ordersToAssign = Order::whereIn('id', $data['order_ids'])->whereNull('rider_id')->get();
        if ($rider->coverageArea) {
            $area = $rider->coverageArea;
            $outsideArea = $ordersToAssign->first(function (Order $order) use ($area) {
                $address = Str::lower($order->recipient_address);
                return ! Str::contains($address, Str::lower($area->province))
                    && ! Str::contains($address, Str::lower($area->city_municipality));
            });

            abort_if($outsideArea, 422, "Parcel {$outsideArea->awb_number} is outside this rider's assigned delivery area.");
        }

        DB::transaction(function () use ($data, $rider) {
            $orders = Order::whereIn('id', $data['order_ids'])->whereNull('rider_id')->get();
            Order::whereIn('id', $data['order_ids'])->whereNull('rider_id')->update([
                'rider_id' => $rider->id,
                'status' => 'in_hub',
                'delivery_status' => 'assigned',
                'assigned_at' => now(),
            ]);
            $rider->update(['status' => 'on_delivery']);
            $rider->performance()->updateOrCreate(['rider_id' => $rider->id], [])->increment('total_assigned', count($data['order_ids']));

            // Broadcast to rider's delivery channel
            foreach ($orders as $order) {
                try {
                    broadcast(new NewDeliveryAssigned($order->fresh()->load('hub')))->toOthers();
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });

        return response()->json($rider->load(['user', 'hub', 'coverageArea', 'performance', 'orders']));
    }

    private function validated(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'name' => [$creating ? 'required' : 'sometimes', 'string'],
            'first_name' => ['nullable', 'string'],
            'last_name' => ['nullable', 'string'],
            'email' => [$creating ? 'required' : 'sometimes', 'email'],
            'password' => ['nullable', 'string', 'min:8'],
            'hub_id' => ['required', 'exists:hubs,id'],
            'coverage_area_id' => ['nullable', 'exists:coverage_areas,id'],
            'vehicle_type' => ['required', 'in:motorcycle,van,tricycle,truck'],
            'plate_number' => ['nullable', 'string'],
            'license_number' => ['nullable', 'string'],
            'status' => ['nullable', 'in:available,on_delivery,off_duty,suspended'],
            'application_status' => ['nullable', 'in:pending_review,approved,rejected'],
            'phone_number' => ['nullable', 'digits:11'],
        ]);
    }
}