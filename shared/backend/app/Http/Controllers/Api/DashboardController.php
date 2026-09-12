<?php

namespace App\Http\Controllers\Api;

use App\Events\HubCapacityAlert;
use App\Http\Controllers\Controller;
use App\Models\Hub;
use App\Services\DashboardMetricsService;

class DashboardController extends Controller
{
    public function metrics(DashboardMetricsService $metrics)
    {
        $payload = $metrics->getMetrics();
        foreach (Hub::where('capacity', '>', 0)->get() as $hub) {
            $percentage = round(($hub->current_stock / $hub->capacity) * 100, 1);
            if ($percentage > 85) broadcast(new HubCapacityAlert($hub, $percentage));
        }
        return response()->json($payload);
    }
}