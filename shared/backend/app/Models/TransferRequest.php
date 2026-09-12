<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TransferRequest extends Model
{
    protected $fillable = ['reference_number', 'from_hub_id', 'to_hub_id', 'requested_by_user_id', 'approved_by_user_id', 'status', 'notes'];
    public function fromHub(): BelongsTo { return $this->belongsTo(Hub::class, 'from_hub_id'); }
    public function toHub(): BelongsTo { return $this->belongsTo(Hub::class, 'to_hub_id'); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by_user_id'); }
    public function items(): BelongsToMany { return $this->belongsToMany(Order::class, 'transfer_request_items'); }
}