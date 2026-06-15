<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyContrastUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_id',
        'contrast_agent_id',
        'dose_ml',
        'route',
        'administered_at',
        'administered_by',
        'notes'
    ];

    protected $casts = [
        'dose_ml' => 'decimal:2',
        'administered_at' => 'datetime'
    ];

    public function study(): BelongsTo
    {
        return $this->belongsTo(RadiologyStudy::class, 'study_id');
    }

    public function contrastAgent(): BelongsTo
    {
        return $this->belongsTo(ContrastAgent::class, 'contrast_agent_id');
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }
}
