<?php

namespace App\Http\Controllers\Api\Alona;

use App\Http\Controllers\Controller;
use App\Models\AlonaDispute;
use App\Models\AlonaDisputeMessage;
use App\Models\AlonaParcel;
use Illuminate\Http\Request;

class AlonaDisputeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = AlonaDispute::with(['parcel.zone', 'parcel.seller', 'parcel.rider.user', 'openedBy', 'assignedTo'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->category, fn ($q, $category) => $q->where('category', $category))
            ->latest();

        if (! $this->isAdmin($user)) {
            $query->where(function ($q) use ($user) {
                $q->where('opened_by', $user->id)
                    ->orWhereHas('parcel', fn ($parcel) => $parcel->where('seller_id', $user->id)->orWhereHas('rider', fn ($rider) => $rider->where('user_id', $user->id)));
            });
        }

        return $query->paginate($request->integer('per_page', 25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'parcel_id' => ['required', 'exists:alona_parcels,id'],
            'category' => ['required', 'in:DAMAGED,LOST,DELAYED,DELIVERY_FAILED,OTHER'],
            'priority' => ['nullable', 'in:LOW,NORMAL,HIGH,URGENT'],
            'description' => ['required', 'string', 'max:5000'],
        ]);
        $parcel = AlonaParcel::findOrFail($data['parcel_id']);
        $this->authorizeParticipant($request, $parcel);

        $dispute = AlonaDispute::create($data + ['opened_by' => $request->user()->id, 'status' => 'OPEN']);
        return response()->json($dispute->load(['parcel', 'openedBy']), 201);
    }

    public function show(Request $request, AlonaDispute $dispute)
    {
        $this->authorizeParticipant($request, $dispute->parcel);
        return $dispute->load(['parcel.zone', 'parcel.seller', 'parcel.rider.user', 'openedBy', 'assignedTo', 'messages.sender']);
    }

    public function update(Request $request, AlonaDispute $dispute)
    {
        $this->requireAlonaAdmin($request);
        $dispute->update($request->validate([
            'status' => ['sometimes', 'in:OPEN,IN_REVIEW,RESOLVED,CLOSED'],
            'priority' => ['sometimes', 'in:LOW,NORMAL,HIGH,URGENT'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'resolution' => ['nullable', 'string', 'max:5000'],
        ]));
        return $dispute->fresh(['parcel', 'openedBy', 'assignedTo']);
    }

    public function message(Request $request, AlonaDispute $dispute)
    {
        $this->authorizeParticipant($request, $dispute->parcel);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $message = AlonaDisputeMessage::create(['dispute_id' => $dispute->id, 'sender_id' => $request->user()->id, 'body' => $data['body']]);
        if ($dispute->status === 'OPEN') $dispute->update(['status' => 'IN_REVIEW']);
        return response()->json($message->load('sender'), 201);
    }

    private function authorizeParticipant(Request $request, AlonaParcel $parcel): void
    {
        $user = $request->user();
        abort_unless($this->isAdmin($user) || (int) $parcel->seller_id === (int) $user->id || (int) optional($user->rider)->id === (int) $parcel->rider_id, 403, 'You are not authorized to access this dispute.');
    }

    private function isAdmin($user): bool
    {
        return $user->hasAnyRole(['Admin', 'Super Admin', 'Logistics Admin', 'admin', 'super_admin', 'logistics_admin']);
    }
}