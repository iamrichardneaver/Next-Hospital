<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContrastAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'generic_name',
        'manufacturer',
        'indications',
        'contraindications',
        'side_effects',
        'dose_ml',
        'route_of_administration',
        'requires_consent',
        'is_active'
    ];

    protected $casts = [
        'dose_ml' => 'decimal:2',
        'requires_consent' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function studyUsage(): HasMany
    {
        return $this->hasMany(StudyContrastUsage::class, 'contrast_agent_id');
    }
}
