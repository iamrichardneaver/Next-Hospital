<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImagingModality extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'category',
        'requires_contrast',
        'requires_sedation',
        'preparation_time_minutes',
        'procedure_time_minutes',
        'base_cost',
        'is_active'
    ];

    protected $casts = [
        'requires_contrast' => 'boolean',
        'requires_sedation' => 'boolean',
        'is_active' => 'boolean',
        'base_cost' => 'decimal:2'
    ];

    public function equipment(): HasMany
    {
        return $this->hasMany(RadiologyEquipment::class, 'modality_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(RadiologyRequest::class, 'modality_id');
    }

    public function studies(): HasMany
    {
        return $this->hasMany(RadiologyStudy::class, 'modality_id');
    }

    public function protocols(): HasMany
    {
        return $this->hasMany(RadiologyProtocol::class, 'modality_id');
    }
}
