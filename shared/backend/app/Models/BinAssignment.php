<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BinAssignment extends Model
{
    public $timestamps = false;
    protected $fillable = ['bin_id', 'order_id', 'scanned_by_user_id', 'assigned_at'];
    protected function casts(): array { return ['assigned_at' => 'datetime']; }
    public function bin(): BelongsTo { return $this->belongsTo(Bin::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}