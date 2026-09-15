<?php

namespace App\Http\Controllers\Api\Alona;

use App\Events\AlonaManifestUpdated;
use App\Http\Controllers\Controller;
use App\Models\AlonaBulkManifest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlonaManifestController extends Controller
{
    use RequiresAlonaAdmin;

    public function index(Request $request)
    {
        $this->requireAlonaAdmin($request);
        return AlonaBulkManifest::with(['seller', 'zone', 'rider', 'agency'])
            ->withCount('parcels')
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->seller_id, fn ($q, $id) => $q->where('seller_id', $id))
            ->latest()->paginate($request->integer('per_page', 25));
    }

    public function show(Request $request, AlonaBulkManifest $manifest)
    {
        $this->requireAlonaAdmin($request);
        return $manifest->load(['seller', 'zone', 'rider', 'agency', 'parcels']);
    }

    public function approve(Request $request, AlonaBulkManifest $manifest)
    {
        $this->requireAlonaAdmin($request);
        abort_unless($manifest->status === 'PENDING_APPROVAL', 422, 'Only pending manifests can be approved.');
        DB::transaction(function () use ($manifest, $request) {
            $manifest->update(['status' => 'APPROVED', 'approved_at' => now(), 'approved_by' => $request->user()->id, 'approved_parcels' => $manifest->parcels()->count()]);
        });
        broadcast(new AlonaManifestUpdated($manifest->fresh(['zone', 'rider', 'agency'])))->toOthers();
        return $manifest->fresh(['seller', 'zone', 'rider', 'agency']);
    }

    public function reject(Request $request, AlonaBulkManifest $manifest)
    {
        $this->requireAlonaAdmin($request);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $manifest->update(['status' => 'REJECTED', 'notes' => $data['reason']]);
        broadcast(new AlonaManifestUpdated($manifest->fresh()))->toOthers();
        return $manifest->fresh();
    }

    public function bulkApprove(Request $request)
    {
        $this->requireAlonaAdmin($request);
        $data = $request->validate(['manifest_ids' => ['required', 'array', 'min:1'], 'manifest_ids.*' => ['integer', 'exists:alona_bulk_manifests,id']]);
        $manifests = AlonaBulkManifest::whereIn('id', $data['manifest_ids'])->where('status', 'PENDING_APPROVAL')->get();
        DB::transaction(function () use ($manifests, $request) {
            foreach ($manifests as $manifest) {
                $manifest->update(['status' => 'APPROVED', 'approved_at' => now(), 'approved_by' => $request->user()->id, 'approved_parcels' => $manifest->parcels()->count()]);
            }
        });
        return response()->json(['approved_count' => $manifests->count()]);
    }
}
