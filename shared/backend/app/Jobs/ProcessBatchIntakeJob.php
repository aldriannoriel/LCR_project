<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessBatchIntakeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;
    public function __construct(public array $scans, public int $hubId) {}
    public function handle(): void
    {
        foreach ($this->scans as $awbNumber) {
            DB::transaction(function () use ($awbNumber) {
                $order = Order::where('awb_number', $awbNumber)->lockForUpdate()->first();
                if ($order && $order->status === 'pending') $order->update(['hub_id' => $this->hubId, 'current_hub_id' => $this->hubId, 'status' => 'received', 'scanned_at' => now()]);
            });
        }
    }
}