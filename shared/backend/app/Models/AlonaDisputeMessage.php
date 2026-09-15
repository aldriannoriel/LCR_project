<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlonaDisputeMessage extends Model
{
    protected $fillable = ['dispute_id', 'sender_id', 'body', 'attachments'];
    protected function casts(): array { return ['attachments' => 'array']; }

    public function dispute(): BelongsTo { return $this->belongsTo(AlonaDispute::class, 'dispute_id'); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sender_id'); }
}