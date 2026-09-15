<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlonaParcel extends Model
{
    protected $fillable = ['tracking_number', 'seller_id', 'manifest_id', 'zone_id', 'rider_id', 'agency_id', 'status', 'recipient_name', 'recipient_phone', 'recipient_address', 'weight_kg', 'declared_value', 'delivery_attempts', 'failed_reason', 'delivered_at', 'returned_at', 'metadata'];
    protected function casts(): array { return ['weight_kg' => 'decimal:2', 'declared_value' => 'decimal:2', 'delivered_at' => 'datetime', 'returned_at' => 'datetime', 'metadata' => 'array']; }
    public function seller(): BelongsTo { return $this->belongsTo(User::class, 'seller_id'); }
    public function manifest(): BelongsTo { return $this->belongsTo(AlonaBulkManifest::class, 'manifest_id'); }
    public function zone(): BelongsTo { return $this->belongsTo(AlonaZone::class, 'zone_id'); }
    public function rider(): BelongsTo { return $this->belongsTo(AlonaRider::class, 'rider_id'); }
    public function agency(): BelongsTo { return $this->belongsTo(AlonaCourierAgency::class, 'agency_id'); }
    public function auditLogs(): HasMany { return $this->hasMany(AlonaAuditLog::class, 'entity_id')->where('entity_type', 'parcel'); }
    public function disputes(): HasMany { return $this->hasMany(AlonaDispute::class, 'parcel_id'); }
}
