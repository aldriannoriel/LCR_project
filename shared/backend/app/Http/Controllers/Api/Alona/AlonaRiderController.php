<?php

namespace App\Http\Controllers\Api\Alona;

use App\Http\Controllers\Controller;
use App\Models\AlonaRider;
use App\Models\AlonaRiderDocument;
use Illuminate\Http\Request;

class AlonaRiderController extends Controller
{
    use RequiresAlonaAdmin;

    public function index(Request $request)
    {
        $this->requireAlonaAdmin($request);

        return AlonaRider::with(['agency', 'documents', 'zones'])
            ->when($request->search, fn ($q, $search) => $q->where(function ($sub) use ($search) {
                $sub->where('full_name', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhere('license_number', 'ilike', "%{$search}%");
            }))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->agency_id, fn ($q, $id) => $q->where('agency_id', $id))
            ->latest()->paginate($request->integer('per_page', 25));
    }

    public function store(Request $request)
    {
        $this->requireAlonaAdmin($request);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'rider_kind' => ['required', 'in:internal,external'],
            'agency_id' => ['nullable', 'exists:alona_courier_agencies,id'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_expiry_date' => ['nullable', 'date'],
            'vehicle_type' => ['nullable', 'string', 'max:50'],
            'plate_number' => ['nullable', 'string', 'max:50'],
        ]);

        abort_if($data['rider_kind'] === 'external' && empty($data['agency_id']), 422, 'External riders require an agency.');

        return response()->json(AlonaRider::create($data + ['created_by' => $request->user()->id]), 201);
    }

    public function show(Request $request, AlonaRider $rider)
    {
        $this->requireAlonaAdmin($request);
        return $rider->load(['agency', 'documents', 'zones', 'parcels']);
    }

    public function update(Request $request, AlonaRider $rider)
    {
        $this->requireAlonaAdmin($request);
        $data = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:150'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'agency_id' => ['nullable', 'exists:alona_courier_agencies,id'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_expiry_date' => ['nullable', 'date'],
            'vehicle_type' => ['nullable', 'string', 'max:50'],
            'plate_number' => ['nullable', 'string', 'max:50'],
        ]);
        abort_if($rider->rider_kind === 'external' && empty($data['agency_id']) && ! $rider->agency_id, 422, 'External riders require an agency.');
        $rider->update($data);
        return $rider->fresh(['agency', 'documents']);
    }

    public function destroy(Request $request, AlonaRider $rider)
    {
        $this->requireAlonaAdmin($request);
        abort_if($rider->parcels()->whereNotIn('status', ['DELIVERED', 'RETURNED'])->exists(), 422, 'Rider has active parcels.');
        $rider->delete();
        return response()->noContent();
    }

    public function status(Request $request, AlonaRider $rider)
    {
        $this->requireAlonaAdmin($request);
        $rider->update($request->validate(['status' => ['required', 'in:active,suspended,on_duty,inactive']]));
        return $rider->fresh(['agency', 'documents', 'zones']);
    }

    public function approve(Request $request, AlonaRider $rider)
    {
        $this->requireAlonaAdmin($request);
        abort_unless($rider->application_status === 'pending_review', 422, 'Only pending applications can be approved.');
        $rider->update(['application_status' => 'approved', 'status' => 'active', 'application_rejection_reason' => null]);
        return $rider->fresh(['agency', 'documents', 'zones']);
    }

    public function reject(Request $request, AlonaRider $rider)
    {
        $this->requireAlonaAdmin($request);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $rider->update(['application_status' => 'rejected', 'status' => 'inactive', 'application_rejection_reason' => $data['reason']]);
        return $rider->fresh(['agency', 'documents', 'zones']);
    }

    public function uploadDocument(Request $request, AlonaRider $rider)
    {
        $this->requireAlonaAdmin($request);
        $data = $request->validate([
            'document_type' => ['required', 'string', 'max:80'],
            'file' => ['required', 'file', 'max:10240'],
            'expires_at' => ['nullable', 'date'],
        ]);
        $document = $rider->documents()->create([
            'document_type' => $data['document_type'],
            'file_path' => $request->file('file')->store('alona/rider-documents', 'private'),
            'expires_at' => $data['expires_at'] ?? null,
        ]);
        return response()->json($document, 201);
    }

    public function verifyDocument(Request $request, AlonaRider $rider, AlonaRiderDocument $document)
    {
        $this->requireAlonaAdmin($request);
        abort_unless($document->rider_id === $rider->id, 404);
        $data = $request->validate([
            'status' => ['required', 'in:verified,rejected'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $document->update($data + ['verified_by' => $request->user()->id, 'verified_at' => now()]);
        $rider->update(['document_status' => $data['status']]);
        return $document->fresh();
    }
}
