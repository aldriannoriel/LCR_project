<?php

namespace App\Events;

use App\Models\AlonaParcel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlonaParcelStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AlonaParcel $parcel) {}
    public function broadcastOn(): array { return [new PrivateChannel('alona.parcels'), new PrivateChannel('alona.parcel.'.$this->parcel->id)]; }
    public function broadcastAs(): string { return 'parcel.status.changed'; }
    public function broadcastWith(): array { return ['parcel' => $this->parcel, 'status' => $this->parcel->status]; }
}
