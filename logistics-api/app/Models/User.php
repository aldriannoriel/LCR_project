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

    protected $fillable = ['name', 'email', 'password', 'hub_id'];

    protected $hidden = ['password', 'remember_token'];

    public function hub()
    {
        return $this->belongsTo(Hub::class);
    }

    public function rider() { return $this->hasOne(Rider::class); }
}