<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'awb_number', 'hub_id', 'status', 'sender_name', 'recipient_name', 'recipient_phone',
        'recipient_address', 'weight_kg', 'flag_reason', 'scanned_at', 'rider_id',
        'assigned_at', 'dispatched_at', 'delivered_at', 'delivery_status',
        'delivery_failure_reason', 'delivery_notes', 'delivery_fee',
        'current_hub_id', 'hub_scanned_at', 'delivery_attempts', 'proof_image_path',
        'delivery_failure_photo_path',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'scanned_at' => 'datetime',
            'hub_scanned_at' => 'datetime',
            'assigned_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
            'delivery_attempts' => 'integer',
        ];
    }

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }

    public function manifests(): BelongsToMany
    {
        return $this->belongsToMany(Manifest::class);
    }

    public function routePlan(): HasOne { return $this->hasOne(OrderRoutePlan::class); }
    public function binAssignments(): HasMany { return $this->hasMany(BinAssignment::class); }
    public function rider(): BelongsTo { return $this->belongsTo(Rider::class); }
    public function currentHub(): BelongsTo { return $this->belongsTo(Hub::class, 'current_hub_id'); }
    public function statusHistories(): HasMany { return $this->hasMany(OrderStatusHistory::class); }
    public function statusHistory(): HasMany { return $this->hasMany(OrderStatusHistory::class); }
    public function returnRecord(): HasOne { return $this->hasOne(ReturnRecord::class); }
}