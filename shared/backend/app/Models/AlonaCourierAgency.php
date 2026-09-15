<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlonaCourierAgency extends Model
{
    protected $fillable = ['name', 'code', 'contact_name', 'contact_email', 'contact_phone', 'api_base_url', 'api_credentials', 'is_active'];
    protected $hidden = ['api_credentials'];
    protected function casts(): array { return ['api_credentials' => 'array', 'is_active' => 'boolean']; }
    public function riders(): HasMany { return $this->hasMany(AlonaRider::class, 'agency_id'); }
    public function zones(): HasMany { return $this->hasMany(AlonaZone::class, 'default_agency_id'); }
}
