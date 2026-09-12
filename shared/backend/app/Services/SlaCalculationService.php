<?php

namespace App\Services;

use Carbon\CarbonInterface;

class SlaCalculationService
{
    public function calculate(CarbonInterface $receivedAt, ?CarbonInterface $sortedAt, ?CarbonInterface $deliveredAt, int $sortingLimitHours = 4, int $finalMileLimitHours = 48): array
    {
        $sortingHours = $sortedAt ? round($receivedAt->diffInMinutes($sortedAt) / 60, 2) : null;
        $finalMileHours = $sortedAt && $deliveredAt ? round($sortedAt->diffInMinutes($deliveredAt) / 60, 2) : null;
        return [
            'sorting_hours' => $sortingHours,
            'final_mile_hours' => $finalMileHours,
            'sorting_breached' => $sortingHours !== null && $sortingHours > $sortingLimitHours,
            'final_mile_breached' => $finalMileHours !== null && $finalMileHours > $finalMileLimitHours,
            'status' => $sortingHours !== null && $sortingHours > $sortingLimitHours ? 'sorting_delay' : ($finalMileHours !== null && $finalMileHours > $finalMileLimitHours ? 'final_mile_delay' : 'within_sla'),
        ];
    }
}