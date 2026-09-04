<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoverageArea extends Model
{
    protected $fillable = ['province', 'city_municipality', 'hub_id'];

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }
}