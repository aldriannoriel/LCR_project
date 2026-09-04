<?php

namespace App\Events;

use App\Models\PickupRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewPickupAssigned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public PickupRequest $pickup)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('rider.' . $this->pickup->assigned_rider_id . '.pickups')];
    }

    public function broadcastWith(): array
    {
        return [
            'pickup' => $this->pickup->load(['seller', 'hub']),
            'message' => 'New pickup assigned: ' . $this->pickup->request_code,
        ];
    }
}
