<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlonaZone extends Model
{
    protected $fillable = ['name', 'code', 'province', 'city_municipality', 'barangays', 'default_rider_id', 'default_agency_id', 'is_active', 'boundary_geojson', 'metadata', 'created_by'];
    protected function casts(): array { return ['barangays' => 'array', 'boundary_geojson' => 'array', 'metadata' => 'array', 'is_active' => 'boolean']; }
    public function defaultRider(): BelongsTo { return $this->belongsTo(AlonaRider::class, 'default_rider_id'); }
    public function defaultAgency(): BelongsTo { return $this->belongsTo(AlonaCourierAgency::class, 'default_agency_id'); }
    public function parcels(): HasMany { return $this->hasMany(AlonaParcel::class, 'zone_id'); }
}
