<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    public $timestamps = false;
    protected $fillable = ['order_id', 'status', 'location_hub_id', 'performed_by_user_id', 'notes', 'created_at'];
    protected function casts(): array { return ['created_at' => 'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function hub(): BelongsTo { return $this->belongsTo(Hub::class, 'location_hub_id'); }
    public function performer(): BelongsTo { return $this->belongsTo(User::class, 'performed_by_user_id'); }
}