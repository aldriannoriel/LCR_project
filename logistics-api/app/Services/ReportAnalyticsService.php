<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ReportAnalyticsService
{
    public function query(array $filters): Builder
    {
        return Order::with(['currentHub.archipelago', 'hub', 'statusHistories'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['hub_id'] ?? null, fn ($query, $id) => $query->where(fn ($query) => $query->where('hub_id', $id)->orWhere('current_hub_id', $id)))
            ->when($filters['region'] ?? null, fn ($query, $region) => $query->whereHas('currentHub.archipelago', fn ($query) => $query->where('name', $region)));
    }

    public function analytics(array $filters): array
    {
        $orders = $this->query($filters)->get();
        $breaches = $orders->map(fn (Order $order) => $this->sla($order));
        $daily = $orders->groupBy(fn (Order $order) => Carbon::parse($order->created_at)->toDateString())->map(fn ($group, $date) => ['date' => $date, 'volume' => $group->count(), 'delivered' => $group->where('status', 'delivered')->count()])->values();

        return [
            'summary' => ['total_orders' => $orders->count(), 'delivered' => $orders->where('status', 'delivered')->count(), 'delivery_rate' => $orders->count() ? round($orders->where('status', 'delivered')->count() / $orders->count() * 100, 1) : 0, 'sorting_breaches' => $breaches->where('status', 'sorting_delay')->count(), 'final_mile_breaches' => $breaches->where('status', 'final_mile_delay')->count()],
            'sla_breaches' => $breaches->filter(fn ($item) => $item['status'] !== 'within_sla')->values(),
            'daily_volume' => $daily,
        ];
    }

    public function sla(Order $order): array
    {
        $history = $order->statusHistories->sortBy('created_at');
        $received = $history->first(fn ($item) => in_array($item->status, ['received', 'in_hub']));
        $sorted = $history->first(fn ($item) => in_array($item->status, ['sorted', 'in_transit']));
        $dispatched = $history->first(fn ($item) => $item->status === 'out_for_delivery');
        $delivered = $history->first(fn ($item) => $item->status === 'delivered');
        $sortingHours = $received && $sorted ? round(Carbon::parse($received->created_at)->diffInMinutes($sorted->created_at) / 60, 2) : null;
        $finalMileHours = $dispatched && $delivered ? round(Carbon::parse($dispatched->created_at)->diffInMinutes($delivered->created_at) / 60, 2) : null;
        $status = $sortingHours > 4 ? 'sorting_delay' : ($finalMileHours > 48 ? 'final_mile_delay' : 'within_sla');
        return ['order_id' => $order->id, 'awb_number' => $order->awb_number, 'sorting_hours' => $sortingHours, 'final_mile_hours' => $finalMileHours, 'status' => $status];
    }
}