<?php

namespace App\Services;

use App\Models\Hub;
use App\Models\Order;
use App\Models\ReturnRecord;
use Illuminate\Support\Facades\Cache;

class DashboardMetricsService
{
    private int $ttl = 60;

    public function getTotalInboundToday(): int
    {
        return Cache::remember('dashboard.inbound_today', $this->ttl, fn () => Order::whereDate('scanned_at', today())->count());
    }

    public function getOutForDeliveryCount(): int
    {
        return Cache::remember('dashboard.out_for_delivery', $this->ttl, fn () => Order::where('status', 'out_for_delivery')->whereNotNull('rider_id')->count());
    }

    public function getHubCapacityOverview(): array
    {
        return Cache::remember('dashboard.hub_capacity', $this->ttl, function () {
            $hubs = Hub::where('capacity', '>', 0)->get(['id', 'capacity', 'current_stock']);
            $capacity = $hubs->sum('capacity');
            $stock = $hubs->sum(fn (Hub $hub) => $hub->current_stock ?? Order::where('current_hub_id', $hub->id)->where('status', 'in_hub')->count());
            return ['capacity' => $capacity, 'current_stock' => $stock, 'utilization_percentage' => $capacity > 0 ? round($stock / $capacity * 100, 1) : 0];
        });
    }

    public function getPendingReturnsCount(): int
    {
        return Cache::remember('dashboard.pending_returns', $this->ttl, fn () => ReturnRecord::whereIn('status', ['pending_intake', 'in_reverse_queue'])->count());
    }

    public function getRegionalVolumeMetrics(): array
    {
        return Cache::remember('dashboard.regional_volume', $this->ttl, fn () => collect(['Luzon', 'Visayas', 'Mindanao'])->mapWithKeys(function (string $region) {
            $query = Order::whereHas('currentHub', fn ($q) => $q->whereHas('archipelago', fn ($q) => $q->where('name', $region)))->orWhereHas('hub', fn ($q) => $q->whereHas('archipelago', fn ($q) => $q->where('name', $region)));
            $total = (clone $query)->count();
            $completed = (clone $query)->where('status', 'delivered')->count();
            return [strtolower($region) => ['region' => $region, 'active_orders' => $total, 'destination_hubs' => $query->with('currentHub')->get()->pluck('currentHub.name')->filter()->unique()->values()->all(), 'completion_rate' => $total ? round($completed / $total * 100, 1) : 0]];
        })->all());
    }

    public function getMetrics(): array
    {
        $yesterday = ['inbound' => Order::whereDate('scanned_at', today()->subDay())->count(), 'out_for_delivery' => Order::where('status', 'out_for_delivery')->whereDate('assigned_at', today()->subDay())->count(), 'returns' => ReturnRecord::whereIn('status', ['pending_intake', 'in_reverse_queue'])->whereDate('created_at', today()->subDay())->count()];
        return ['total_inbound_today' => $this->getTotalInboundToday(), 'out_for_delivery' => $this->getOutForDeliveryCount(), 'hub_capacity' => $this->getHubCapacityOverview(), 'pending_returns' => $this->getPendingReturnsCount(), 'regional_volume' => $this->getRegionalVolumeMetrics(), 'trends' => ['total_inbound_today' => $yesterday['inbound'], 'out_for_delivery' => $yesterday['out_for_delivery'], 'pending_returns' => $yesterday['returns']]];
    }
}