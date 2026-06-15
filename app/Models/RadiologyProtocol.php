<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyProtocol extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'modality_id',
        'body_part',
        'description',
        'technical_parameters',
        'patient_preparation',
        'contraindications',
        'requires_contrast',
        'is_active'
    ];

    protected $casts = [
        'technical_parameters' => 'array',
        'requires_contrast' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function modality(): BelongsTo
    {
        return $this->belongsTo(ImagingModality::class, 'modality_id');
    }
}
