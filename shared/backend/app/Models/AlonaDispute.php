<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlonaDispute extends Model
{
    protected $fillable = ['parcel_id', 'opened_by', 'assigned_to', 'category', 'status', 'priority', 'description', 'resolution'];

    public function parcel(): BelongsTo { return $this->belongsTo(AlonaParcel::class, 'parcel_id'); }
    public function openedBy(): BelongsTo { return $this->belongsTo(User::class, 'opened_by'); }
    public function assignedTo(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function messages(): HasMany { return $this->hasMany(AlonaDisputeMessage::class, 'dispute_id'); }
}