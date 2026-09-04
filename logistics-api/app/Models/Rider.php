<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rider extends Model
{
    protected $fillable = ['user_id', 'hub_id', 'vehicle_type', 'plate_number', 'status', 'phone_number'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function hub(): BelongsTo { return $this->belongsTo(Hub::class); }
    public function performance(): HasOne { return $this->hasOne(RiderPerformance::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function manifests(): HasMany { return $this->hasMany(TransferManifest::class); }
}