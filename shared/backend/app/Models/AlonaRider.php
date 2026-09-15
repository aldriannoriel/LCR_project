<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlonaRider extends Model
{
    protected $fillable = ['user_id', 'agency_id', 'rider_kind', 'full_name', 'email', 'phone', 'license_number', 'license_expiry_date', 'vehicle_type', 'plate_number', 'status', 'application_status', 'application_rejection_reason', 'document_status', 'is_default_assignment', 'metadata', 'created_by'];
    protected function casts(): array { return ['license_expiry_date' => 'date', 'metadata' => 'array', 'is_default_assignment' => 'boolean']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function agency(): BelongsTo { return $this->belongsTo(AlonaCourierAgency::class, 'agency_id'); }
    public function documents(): HasMany { return $this->hasMany(AlonaRiderDocument::class, 'rider_id'); }
    public function zones(): HasMany { return $this->hasMany(AlonaZone::class, 'default_rider_id'); }
    public function parcels(): HasMany { return $this->hasMany(AlonaParcel::class, 'rider_id'); }
}
