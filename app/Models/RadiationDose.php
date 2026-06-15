<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiationDose extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_id',
        'dose_length_product',
        'effective_dose',
        'ctdi_vol',
        'dlp',
        'dose_parameters'
    ];

    protected $casts = [
        'dose_length_product' => 'decimal:2',
        'effective_dose' => 'decimal:4',
        'ctdi_vol' => 'decimal:2',
        'dlp' => 'decimal:2',
        'dose_parameters' => 'array'
    ];

    public function study(): BelongsTo
    {
        return $this->belongsTo(RadiologyStudy::class, 'study_id');
    }
}
