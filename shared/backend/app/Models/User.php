<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'hub_id',
        'first_name',
        'last_name',
        'middle_initial',
        'sex',
        'phone_number',
        'birthdate',
        'age',
        'province',
        'city_municipality',
        'barangay',
        'street_address',
        'business_name',
        'id_document_path',
        'business_permit_path',
        'approval_status',
        'rejection_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'age' => 'integer',
        ];
    }

    public function getFullNameAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return trim("{$this->first_name} {$this->middle_initial} {$this->last_name}");
        }

        return $this->name ?? '';
    }

    public function hub()
    {
        return $this->belongsTo(Hub::class);
    }

    public function rider()
    {
        return $this->hasOne(Rider::class);
    }
}