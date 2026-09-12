<?php

namespace App\Events;

use App\Models\Hub;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HubCapacityAlert implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;
    public function __construct(public Hub $hub, public float $utilizationPercentage) {}
    public function broadcastOn(): array { return [new Channel('dashboard.activity')]; }
    public function broadcastAs(): string { return 'hub.capacity-alert'; }
    public function broadcastWith(): array { return ['hub_id' => $this->hub->id, 'hub_name' => $this->hub->name, 'utilization_percentage' => $this->utilizationPercentage, 'timestamp' => now()->toIso8601String()]; }
}