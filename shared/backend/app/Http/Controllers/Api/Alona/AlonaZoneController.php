<?php

namespace App\Http\Controllers\Api\Alona;

use App\Http\Controllers\Controller;
use App\Models\AlonaZone;
use Illuminate\Http\Request;

class AlonaZoneController extends Controller
{
    use RequiresAlonaAdmin;

    public function index(Request $request)
    {
        $this->requireAlonaAdmin($request);
        return AlonaZone::with(['defaultRider', 'defaultAgency'])
            ->when($request->search, fn ($q, $search) => $q->where('name', 'ilike', "%{$search}%"))
            ->when($request->boolean('include_inactive'), fn ($q) => $q, fn ($q) => $q->where('is_active', true))
            ->orderBy('name')->paginate($request->integer('per_page', 50));
    }

    public function store(Request $request)
    {
        $this->requireAlonaAdmin($request);
        return response()->json(AlonaZone::create($request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'unique:alona_zones,code'],
            'province' => ['nullable', 'string', 'max:150'],
            'city_municipality' => ['nullable', 'string', 'max:150'],
            'barangays' => ['nullable', 'array'],
            'boundary_geojson' => ['nullable', 'array'],
            'default_rider_id' => ['nullable', 'exists:alona_riders,id'],
            'default_agency_id' => ['nullable', 'exists:alona_courier_agencies,id'],
            'created_by' => ['prohibited'],
        ]) + ['created_by' => $request->user()->id]), 201);
    }

    public function show(Request $request, AlonaZone $zone)
    {
        $this->requireAlonaAdmin($request);
        return $zone->load(['defaultRider', 'defaultAgency', 'parcels']);
    }

    public function update(Request $request, AlonaZone $zone)
    {
        $this->requireAlonaAdmin($request);
        $zone->update($request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:150'],
            'city_municipality' => ['nullable', 'string', 'max:150'],
            'barangays' => ['nullable', 'array'],
            'boundary_geojson' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]));
        return $zone->fresh(['defaultRider', 'defaultAgency']);
    }

    public function destroy(Request $request, AlonaZone $zone)
    {
        $this->requireAlonaAdmin($request);
        $zone->update(['is_active' => false]);
        return response()->noContent();
    }

    public function assignDefault(Request $request, AlonaZone $zone)
    {
        $this->requireAlonaAdmin($request);
        $data = $request->validate([
            'rider_id' => ['nullable', 'exists:alona_riders,id'],
            'agency_id' => ['nullable', 'exists:alona_courier_agencies,id'],
        ]);
        $zone->update(['default_rider_id' => $data['rider_id'] ?? null, 'default_agency_id' => $data['agency_id'] ?? null]);
        return $zone->fresh(['defaultRider', 'defaultAgency']);
    }
}
