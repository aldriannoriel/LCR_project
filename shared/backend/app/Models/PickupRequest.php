<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_code',
        'seller_id',
        'hub_id',
        'assigned_rider_id',
        'contact_person',
        'contact_number',
        'province',
        'city_municipality',
        'barangay',
        'pickup_address',
        'scheduled_date',
        'time_slot',
        'estimated_parcels',
        'package_type',
        'special_instructions',
        'status',
        'verified_by_user_id',
        'verified_at',
        'verification_notes',
        'completed_at',
        'actual_parcels_collected',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'verified_at' => 'datetime',
            'completed_at' => 'datetime',
            'estimated_parcels' => 'integer',
            'actual_parcels_collected' => 'integer',
        ];
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function hub()
    {
        return $this->belongsTo(Hub::class);
    }

    public function rider()
    {
        return $this->belongsTo(Rider::class, 'assigned_rider_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}
