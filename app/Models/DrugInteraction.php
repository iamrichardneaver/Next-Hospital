<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'drug1_id',
        'drug2_id',
        'severity',
        'description',
        'clinical_effect',
        'management',
        'is_active'
    ];

    /**
     * Get the first drug in the interaction.
     */
    public function drug1(): BelongsTo
    {
        return $this->belongsTo(Drug::class, 'drug1_id');
    }

    /**
     * Get the second drug in the interaction.
     */
    public function drug2(): BelongsTo
    {
        return $this->belongsTo(Drug::class, 'drug2_id');
    }

    /**
     * Scope to get active interactions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get interactions by severity.
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Check if two drugs have an interaction.
     */
    public static function checkInteraction($drug1Id, $drug2Id)
    {
        return static::active()
            ->where(function($query) use ($drug1Id, $drug2Id) {
                $query->where('drug1_id', $drug1Id)->where('drug2_id', $drug2Id)
                      ->orWhere('drug1_id', $drug2Id)->where('drug2_id', $drug1Id);
            })
            ->with(['drug1', 'drug2'])
            ->first();
    }

    /**
     * Get all interactions for a list of drugs.
     */
    public static function checkMultipleDrugs(array $drugIds)
    {
        $interactions = [];
        
        for ($i = 0; $i < count($drugIds); $i++) {
            for ($j = $i + 1; $j < count($drugIds); $j++) {
                $interaction = static::checkInteraction($drugIds[$i], $drugIds[$j]);
                if ($interaction) {
                    $interactions[] = $interaction;
                }
            }
        }
        
        return $interactions;
    }

    /**
     * Get severity color for display.
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'minor' => 'success',
            'moderate' => 'warning',
            'major' => 'danger',
            'severe' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get severity badge class.
     */
    public function getSeverityBadgeClassAttribute(): string
    {
        return match($this->severity) {
            'minor' => 'badge-success',
            'moderate' => 'badge-warning',
            'major' => 'badge-danger',
            'severe' => 'badge-danger',
            default => 'badge-secondary'
        };
    }
}