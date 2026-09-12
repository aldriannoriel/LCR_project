<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MajorDelayNotification implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;
    public function __construct(public Order $order, public string $reason) {}
    public function broadcastOn(): array { return [new Channel('dashboard.activity')]; }
    public function broadcastAs(): string { return 'order.major-delay'; }
    public function broadcastWith(): array { return ['order_id' => $this->order->id, 'awb_number' => $this->order->awb_number, 'reason' => $this->reason, 'timestamp' => now()->toIso8601String()]; }
}