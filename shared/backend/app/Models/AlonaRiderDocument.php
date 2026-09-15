<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlonaRiderDocument extends Model
{
    protected $fillable = ['rider_id', 'document_type', 'file_path', 'status', 'expires_at', 'verified_by', 'verified_at', 'rejection_reason'];
    protected function casts(): array { return ['expires_at' => 'date', 'verified_at' => 'datetime']; }
    public function rider(): BelongsTo { return $this->belongsTo(AlonaRider::class, 'rider_id'); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
}
