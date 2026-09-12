<?php

namespace App\Services;

use App\Models\Bin;
use App\Models\BinAssignment;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class BinAssignmentService
{
    public function assign(Order $order, int $targetHubId, int $userId): BinAssignment
    {
        return DB::transaction(function () use ($order, $targetHubId, $userId) {
            $bin = Bin::where('hub_id', $order->hub_id)->where('target_hub_id', $targetHubId)->where('status', 'active')->whereColumn('current_count', '<', 'capacity')->lockForUpdate()->firstOrFail();
            $assignment = BinAssignment::firstOrCreate(['bin_id' => $bin->id, 'order_id' => $order->id], ['scanned_by_user_id' => $userId, 'assigned_at' => now()]);
            if ($assignment->wasRecentlyCreated) {
                $bin->increment('current_count');
                $bin->refresh();
                if ($bin->current_count >= $bin->capacity) $bin->update(['status' => 'full']);
            }
            return $assignment->load('bin.targetHub');
        });
    }
}