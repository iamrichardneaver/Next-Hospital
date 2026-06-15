<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_id',
        'radiologist_id',
        'findings',
        'impression',
        'recommendations',
        'status',
        'dictated_date',
        'transcribed_date',
        'signed_date',
        'transcribed_by',
        'amendment_reason',
        'selected_images'
    ];

    protected $casts = [
        'dictated_date' => 'datetime',
        'transcribed_date' => 'datetime',
        'signed_date' => 'datetime',
        'selected_images' => 'array'
    ];

    public function study(): BelongsTo
    {
        return $this->belongsTo(RadiologyStudy::class, 'study_id');
    }

    public function radiologist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'radiologist_id');
    }

    public function transcribedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transcribed_by');
    }

    public function isFinal(): bool
    {
        return $this->status === 'final';
    }

    public function isSigned(): bool
    {
        return $this->signed_date !== null;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeAmended(): bool
    {
        return $this->status === 'final' && $this->isSigned();
    }

    /**
     * Get the selected images as objects
     */
    public function getSelectedImagesObjects()
    {
        if (!$this->selected_images || !is_array($this->selected_images) || empty($this->selected_images)) {
            return collect();
        }

        return \App\Models\RadiologyImage::whereIn('id', $this->selected_images)->get();
    }
}
