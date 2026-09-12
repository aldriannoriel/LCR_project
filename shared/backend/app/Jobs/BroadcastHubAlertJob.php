<?php

namespace App\Jobs;

use App\Events\HubCapacityAlert;
use App\Models\Hub;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastHubAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;
    public function __construct(public int $hubId, public float $utilizationPercentage) {}
    public function handle(): void { $hub = Hub::find($this->hubId); if ($hub) broadcast(new HubCapacityAlert($hub, $this->utilizationPercentage)); }
}