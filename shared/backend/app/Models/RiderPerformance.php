<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderPerformance extends Model
{
    protected $table = 'rider_performance';
    public $timestamps = false;
    protected $fillable = [
        'rider_id',
        'total_assigned',
        'total_completed',
        'total_failed',
        'successful_deliveries',
        'failed_deliveries',
        'successful_pickups',
        'total_deliveries',
        'total_earnings',
        'delivery_rate',
        'pickup_rate',
        'on_time_rate',
        'rating',
    ];
    protected function casts(): array {
        return [
            'on_time_rate' => 'decimal:2',
            'rating' => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'delivery_rate' => 'decimal:2',
            'pickup_rate' => 'decimal:2',
        ];
    }
    public function rider(): BelongsTo { return $this->belongsTo(Rider::class); }
}