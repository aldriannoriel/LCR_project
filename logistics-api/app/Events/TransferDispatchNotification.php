<?php

namespace App\Events;

use App\Models\TransferRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransferDispatchNotification implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;
    public function __construct(public TransferRequest $transfer) {}
    public function broadcastOn(): array { return [new Channel('dashboard.activity')]; }
    public function broadcastAs(): string { return 'transfer.dispatched'; }
    public function broadcastWith(): array { return ['reference_number' => $this->transfer->reference_number, 'from_hub' => $this->transfer->fromHub->name, 'to_hub' => $this->transfer->toHub->name, 'timestamp' => now()->toIso8601String()]; }
}