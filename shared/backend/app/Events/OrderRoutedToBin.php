<?php

namespace App\Events;

use App\Models\BinAssignment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderRoutedToBin implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;
    public function __construct(public BinAssignment $assignment) {}
    public function broadcastOn(): array { return [new PrivateChannel('hub.'.$this->assignment->bin->hub_id)]; }
    public function broadcastAs(): string { return 'order.routed-to-bin'; }
    public function broadcastWith(): array { return ['order_id' => $this->assignment->order_id, 'awb_number' => $this->assignment->order->awb_number, 'bin_code' => $this->assignment->bin->bin_code, 'target_hub_name' => $this->assignment->bin->targetHub->name, 'timestamp' => now()->toIso8601String()]; }
}