<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadiologyDepartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'location',
        'contact_phone',
        'contact_email',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function equipment(): HasMany
    {
        return $this->hasMany(RadiologyEquipment::class);
    }

    public function technicians(): HasMany
    {
        return $this->hasMany(RadiologyTechnician::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(RadiologyRequest::class);
    }
}
