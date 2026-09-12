<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRoutePlan extends Model
{
    public $timestamps = false;
    protected $fillable = ['order_id', 'national_hub_id', 'gateway_hub_id', 'regional_hub_id', 'route_path_json'];
    protected function casts(): array { return ['route_path_json' => 'array', 'created_at' => 'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function regionalHub(): BelongsTo { return $this->belongsTo(Hub::class, 'regional_hub_id'); }
}