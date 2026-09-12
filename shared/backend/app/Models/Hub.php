<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hub extends Model
{
    protected $fillable = ['name', 'code', 'address', 'capacity', 'current_stock', 'hub_type', 'type', 'archipelago_id', 'parent_hub_id'];
    protected function casts(): array { return ['capacity' => 'integer', 'current_stock' => 'integer']; }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function outboundOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'origin_hub_id');
    }

    public function inboundOrders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function coverageAreas(): HasMany { return $this->hasMany(CoverageArea::class); }
    public function bins(): HasMany { return $this->hasMany(Bin::class); }
    public function parentHub(): BelongsTo { return $this->belongsTo(Hub::class, 'parent_hub_id'); }
    public function archipelago(): BelongsTo { return $this->belongsTo(Archipelago::class); }
    public function riders(): HasMany { return $this->hasMany(Rider::class); }
}