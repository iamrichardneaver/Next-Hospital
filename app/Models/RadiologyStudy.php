<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RadiologyStudy extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_uid',
        'request_id',
        'patient_id',
        'modality_id',
        'equipment_id',
        'study_description',
        'study_notes',
        'status',
        'study_date',
        'completed_date',
        'technician_id',
        'radiologist_id',
        'technique_notes',
        'study_parameters'
    ];

    protected $casts = [
        'study_date' => 'datetime',
        'completed_date' => 'datetime',
        'study_parameters' => 'array'
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(RadiologyRequest::class, 'request_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function modality(): BelongsTo
    {
        return $this->belongsTo(ImagingModality::class, 'modality_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(RadiologyEquipment::class, 'equipment_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(RadiologyTechnician::class, 'technician_id');
    }

    public function radiologist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'radiologist_id');
    }

    public function series(): HasMany
    {
        return $this->hasMany(RadiologySeries::class, 'study_id');
    }

    public function images(): HasManyThrough
    {
        return $this->hasManyThrough(RadiologyImage::class, RadiologySeries::class, 'study_id', 'series_id');
    }

    public function report(): HasOne
    {
        return $this->hasOne(RadiologyReport::class, 'study_id');
    }

    public function contrastUsage(): HasMany
    {
        return $this->hasMany(StudyContrastUsage::class, 'study_id');
    }

    public function radiationDose(): HasOne
    {
        return $this->hasOne(RadiationDose::class, 'study_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function hasReport(): bool
    {
        return $this->report()->exists();
    }

    public function getTotalImagesCount(): int
    {
        return $this->images()->count();
    }
}