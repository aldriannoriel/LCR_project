<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('hub.{hubId}', fn ($user, $hubId) => $user->hub_id === null || (int) $user->hub_id === (int) $hubId);

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    if (! $conversation) return false;
    return $conversation->user_one_id === $user->id || $conversation->user_two_id === $user->id;
});

Broadcast::channel('user.{userId}', fn ($user, $userId) => (int) $user->id === (int) $userId);

// Rider-scoped private channels — only the rider who owns the channel can subscribe.
Broadcast::channel('rider.{riderId}.pickups', function ($user, $riderId) {
    return (int) optional($user->rider)->id === (int) $riderId;
});

Broadcast::channel('rider.{riderId}.deliveries', function ($user, $riderId) {
    return (int) optional($user->rider)->id === (int) $riderId;
});

Broadcast::channel('alona.parcels', function ($user) {
    return $user->hasAnyRole(['Admin', 'Super Admin', 'Logistics Admin', 'admin', 'super_admin', 'logistics_admin']);
});

Broadcast::channel('alona.manifests', function ($user) {
    return $user->hasAnyRole(['Admin', 'Super Admin', 'Logistics Admin', 'admin', 'super_admin', 'logistics_admin']);
});

Broadcast::channel('alona.parcel.{parcelId}', function ($user, $parcelId) {
    $parcel = \App\Models\AlonaParcel::find($parcelId);
    return $parcel && (
        $user->hasAnyRole(['Admin', 'Super Admin', 'Logistics Admin', 'admin', 'super_admin', 'logistics_admin'])
        || (int) $parcel->seller_id === (int) $user->id
        || (int) optional($user->rider)->id === (int) $parcel->rider_id
    );
});