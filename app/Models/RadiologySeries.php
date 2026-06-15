<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadiologySeries extends Model
{
    use HasFactory;

    protected $fillable = [
        'series_uid',
        'study_id',
        'series_number',
        'series_description',
        'body_part_examined',
        'view_position',
        'number_of_instances',
        'series_parameters'
    ];

    protected $casts = [
        'series_parameters' => 'array'
    ];

    public function study(): BelongsTo
    {
        return $this->belongsTo(RadiologyStudy::class, 'study_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(RadiologyImage::class, 'series_id');
    }

    public function getImageCount(): int
    {
        return $this->images()->count();
    }
}
