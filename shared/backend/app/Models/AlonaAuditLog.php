<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlonaAuditLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['actor_id', 'entity_type', 'entity_id', 'action', 'previous_status', 'new_status', 'reason', 'changes', 'ip_address', 'created_at'];
    protected function casts(): array { return ['changes' => 'array', 'created_at' => 'datetime']; }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
