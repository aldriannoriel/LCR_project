<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlonaBulkManifest extends Model
{
    protected $fillable = ['manifest_number', 'seller_id', 'zone_id', 'rider_id', 'agency_id', 'status', 'total_parcels', 'approved_parcels', 'submitted_at', 'approved_at', 'approved_by', 'dispatched_at', 'notes', 'metadata'];
    protected function casts(): array { return ['submitted_at' => 'datetime', 'approved_at' => 'datetime', 'dispatched_at' => 'datetime', 'metadata' => 'array']; }
    public function seller(): BelongsTo { return $this->belongsTo(User::class, 'seller_id'); }
    public function zone(): BelongsTo { return $this->belongsTo(AlonaZone::class, 'zone_id'); }
    public function rider(): BelongsTo { return $this->belongsTo(AlonaRider::class, 'rider_id'); }
    public function agency(): BelongsTo { return $this->belongsTo(AlonaCourierAgency::class, 'agency_id'); }
    public function parcels(): HasMany { return $this->hasMany(AlonaParcel::class, 'manifest_id'); }
}
