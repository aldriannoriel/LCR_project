<?php

namespace App\Events;

use App\Models\AlonaBulkManifest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlonaManifestUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AlonaBulkManifest $manifest) {}
    public function broadcastOn(): array { return [new PrivateChannel('alona.manifests')]; }
    public function broadcastAs(): string { return 'manifest.updated'; }
    public function broadcastWith(): array { return ['manifest' => $this->manifest]; }
}
