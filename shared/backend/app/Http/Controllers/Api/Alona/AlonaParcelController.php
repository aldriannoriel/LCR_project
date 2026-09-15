<?php

namespace App\Http\Controllers\Api\Alona;

use App\Events\AlonaParcelStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\AlonaAuditLog;
use App\Models\AlonaParcel;
use App\Models\AlonaRider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlonaParcelController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = AlonaParcel::with(['zone', 'rider', 'agency']);
        if (! $user->hasAnyRole(['Admin', 'Super Admin', 'Logistics Admin', 'admin', 'super_admin', 'logistics_admin'])) {
            $query->where(function ($q) use ($user) {
                $q->where('seller_id', $user->id)->orWhereIn('rider_id', $user->rider ? [$user->rider->id] : []);
            });
        }
        return $query->when($request->tracking_number, fn ($q, $value) => $q->where('tracking_number', $value))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->zone_id, fn ($q, $id) => $q->where('zone_id', $id))
            ->latest()->paginate($request->integer('per_page', 25));
    }

    public function show(Request $request, AlonaParcel $parcel)
    {
        $this->authorizeParcel($request, $parcel);
        return $parcel->load(['seller', 'zone', 'rider', 'agency', 'manifest']);
    }

    public function changeStatus(Request $request, AlonaParcel $parcel)
    {
        $this->authorizeParcel($request, $parcel, true);
        $data = $request->validate([
            'status' => ['required', 'in:AT_SORTING_CENTER,OUT_FOR_DELIVERY,DELIVERY_FAILED,DELIVERED,RETURNED'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $oldStatus = $parcel->status;
        abort_if($oldStatus === $data['status'], 422, 'Parcel is already in that status.');
        $allowed = [
            'AT_SORTING_CENTER' => ['SORTED'],
            'SORTED' => ['ASSIGNED_TO_RIDER'],
            'ASSIGNED_TO_RIDER' => ['OUT_FOR_DELIVERY'],
            'OUT_FOR_DELIVERY' => ['DELIVERED', 'DELIVERY_FAILED'],
            'DELIVERY_FAILED' => ['OUT_FOR_DELIVERY', 'RETURNED'],
            'DELIVERED' => [],
            'RETURNED' => [],
        ];
        abort_unless(in_array($data['status'], $allowed[$oldStatus] ?? [], true), 422, 'Invalid parcel status transition.');
        DB::transaction(function () use ($parcel, $data, $oldStatus, $request) {
            $updates = ['status' => $data['status']];
            if ($data['status'] === 'DELIVERY_FAILED') $updates += ['delivery_attempts' => $parcel->delivery_attempts + 1, 'failed_reason' => $data['reason'] ?? null];
            if ($data['status'] === 'DELIVERED') $updates['delivered_at'] = now();
            if ($data['status'] === 'RETURNED') $updates['returned_at'] = now();
            $parcel->update($updates);
            AlonaAuditLog::create(['actor_id' => $request->user()->id, 'entity_type' => 'parcel', 'entity_id' => $parcel->id, 'action' => 'STATUS_CHANGED', 'previous_status' => $oldStatus, 'new_status' => $data['status'], 'reason' => $data['reason'] ?? null, 'ip_address' => $request->ip()]);
        });
        $parcel = $parcel->fresh(['zone', 'rider', 'agency']);
        broadcast(new AlonaParcelStatusChanged($parcel))->toOthers();
        return $parcel;
    }

    public function assign(Request $request, AlonaParcel $parcel)
    {
        $this->requireAlonaAdmin($request);
        abort_unless(in_array($parcel->status, ['SORTED', 'AT_SORTING_CENTER'], true), 422, 'Only sorted parcels can be assigned.');

        $data = $request->validate(['rider_id' => ['required', 'exists:alona_riders,id']]);
        $rider = AlonaRider::findOrFail($data['rider_id']);
        abort_if(in_array($rider->status, ['suspended'], true), 422, 'Suspended riders cannot receive parcels.');

        DB::transaction(function () use ($parcel, $rider, $request) {
            $oldStatus = $parcel->status;
            $parcel->update(['rider_id' => $rider->id, 'agency_id' => $rider->agency_id, 'status' => 'ASSIGNED_TO_RIDER']);
            AlonaAuditLog::create([
                'actor_id' => $request->user()->id,
                'entity_type' => 'parcel',
                'entity_id' => $parcel->id,
                'action' => 'ASSIGNED',
                'previous_status' => $oldStatus,
                'new_status' => 'ASSIGNED_TO_RIDER',
                'changes' => ['rider_id' => $rider->id, 'agency_id' => $rider->agency_id],
                'ip_address' => $request->ip(),
            ]);
        });

        $parcel = $parcel->fresh(['zone', 'rider', 'agency']);
        broadcast(new AlonaParcelStatusChanged($parcel))->toOthers();
        return $parcel;
    }

    public function audit(Request $request, AlonaParcel $parcel)
    {
        $this->authorizeParcel($request, $parcel);
        return AlonaAuditLog::where('entity_type', 'parcel')->where('entity_id', $parcel->id)->with('actor')->latest()->paginate($request->integer('per_page', 50));
    }

    private function authorizeParcel(Request $request, AlonaParcel $parcel, bool $write = false): void
    {
        $user = $request->user();
        $admin = $user->hasAnyRole(['Admin', 'Super Admin', 'Logistics Admin', 'admin', 'super_admin', 'logistics_admin']);
        $riderOwns = $user->rider && $parcel->rider_id === $user->rider->id;
        abort_unless($admin || $parcel->seller_id === $user->id || (! $write && $riderOwns) || ($write && $riderOwns), 403, 'You are not authorized to access this parcel.');
    }
}
