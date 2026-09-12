<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bin extends Model
{
    protected $fillable = ['hub_id', 'bin_code', 'capacity', 'current_count', 'target_hub_id', 'status'];
    protected function casts(): array { return ['capacity' => 'integer', 'current_count' => 'integer']; }
    public function hub(): BelongsTo { return $this->belongsTo(Hub::class); }
    public function targetHub(): BelongsTo { return $this->belongsTo(Hub::class, 'target_hub_id'); }
    public function assignments(): HasMany { return $this->hasMany(BinAssignment::class); }
}