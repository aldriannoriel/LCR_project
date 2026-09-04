<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRecord extends Model
{
    protected $table = 'returns';
    protected $fillable = ['order_id', 'return_reason', 'status', 'action_taken_by', 'action_notes'];
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function actionTaker(): BelongsTo { return $this->belongsTo(User::class, 'action_taken_by'); }
}