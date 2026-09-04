<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TransferManifest extends Model
{
    protected $fillable = ['manifest_number', 'origin_hub_id', 'destination_hub_id', 'status', 'total_orders', 'generated_by', 'dispatched_at'];
    protected function casts(): array { return ['dispatched_at' => 'datetime']; }
    public function originHub(): BelongsTo { return $this->belongsTo(Hub::class, 'origin_hub_id'); }
    public function destinationHub(): BelongsTo { return $this->belongsTo(Hub::class, 'destination_hub_id'); }
    public function orders(): BelongsToMany { return $this->belongsToMany(Order::class, 'transfer_manifest_order'); }
    public function rider(): BelongsTo { return $this->belongsTo(Rider::class); }
}