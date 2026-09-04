<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\RiderPerformance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiderController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate(['search' => ['nullable', 'string'], 'hub_id' => ['nullable', 'integer'], 'archipelago_id' => ['nullable', 'integer'], 'status' => ['nullable', 'in:available,on_delivery,off_duty,suspended'], 'vehicle_type' => ['nullable', 'in:motorcycle,van,tricycle,truck'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        return response()->json(Rider::with(['hub', 'user', 'performance'])->when($validated['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('plate_number', 'like', "%{$search}%")->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"))))->when($validated['hub_id'] ?? null, fn ($q, $id) => $q->where('hub_id', $id))->when($validated['archipelago_id'] ?? null, fn ($q, $id) => $q->whereHas('hub', fn ($q) => $q->where('archipelago_id', $id)))->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status))->when($validated['vehicle_type'] ?? null, fn ($q, $type) => $q->where('vehicle_type', $type))->latest()->paginate($validated['per_page'] ?? 10));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        return DB::transaction(function () use ($data) { $user = \App\Models\User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => bcrypt($data['password'] ?? 'password123'), 'hub_id' => $data['hub_id']]); $rider = Rider::create(['user_id' => $user->id, ...collect($data)->only(['hub_id', 'vehicle_type', 'plate_number', 'status', 'phone_number'])->all()]); RiderPerformance::create(['rider_id' => $rider->id]); return response()->json($rider->load(['user', 'hub', 'performance']), 201); });
    }

    public function update(Request $request, Rider $rider)
    {
        $data = $this->validated($request, false); $rider->update(collect($data)->only(['hub_id', 'vehicle_type', 'plate_number', 'status', 'phone_number'])->all()); $rider->user->update(collect($data)->only(['name', 'email'])->all()); return response()->json($rider->load(['user', 'hub', 'performance']));
    }

    public function status(Request $request, Rider $rider)
    {
        $data = $request->validate(['status' => ['required', 'in:available,on_delivery,off_duty,suspended']]); $rider->update($data); return response()->json($rider->load(['user', 'hub', 'performance']));
    }

    public function details(Rider $rider)
    {
        return response()->json($rider->load(['user', 'hub', 'performance', 'orders.routePlan'])->load(['manifests' => fn ($query) => $query->whereIn('status', ['draft', 'dispatched'])]));
    }

    public function assignOrders(Request $request)
    {
        $data = $request->validate(['rider_id' => ['required', 'exists:riders,id'], 'order_ids' => ['required', 'array', 'min:1'], 'order_ids.*' => ['integer', 'exists:orders,id']]);
        $rider = Rider::findOrFail($data['rider_id']); $limit = ['motorcycle' => 30, 'tricycle' => 50, 'van' => 100, 'truck' => 300][$rider->vehicle_type]; abort_if(count($data['order_ids']) > $limit, 422, "This vehicle can carry at most {$limit} packages.");
        DB::transaction(function () use ($data, $rider) { \App\Models\Order::whereIn('id', $data['order_ids'])->whereNull('rider_id')->update(['rider_id' => $rider->id, 'status' => 'out_for_delivery', 'delivery_status' => 'assigned', 'assigned_at' => now()]); $rider->update(['status' => 'on_delivery']); $rider->performance()->updateOrCreate(['rider_id' => $rider->id], [])->increment('total_assigned', count($data['order_ids'])); });
        return response()->json($rider->load(['user', 'hub', 'performance', 'orders']));
    }

    private function validated(Request $request, bool $creating = true): array
    {
        return $request->validate(['name' => [$creating ? 'required' : 'sometimes', 'string'], 'email' => [$creating ? 'required' : 'sometimes', 'email'], 'password' => ['nullable', 'string', 'min:8'], 'hub_id' => ['required', 'exists:hubs,id'], 'vehicle_type' => ['required', 'in:motorcycle,van,tricycle,truck'], 'plate_number' => ['nullable', 'string'], 'status' => ['nullable', 'in:available,on_delivery,off_duty,suspended'], 'phone_number' => ['nullable', 'string']]);
    }
}