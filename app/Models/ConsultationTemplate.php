<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'specialty',
        'template_data',
        'is_active',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'template_data' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Get the user who created the template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get templates by specialty.
     */
    public function scopeBySpecialty($query, $specialty)
    {
        return $query->where('specialty', $specialty);
    }

    /**
     * Scope to get system templates.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to get custom templates.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Get common specialties.
     */
    public static function getSpecialties()
    {
        return [
            'General Medicine',
            'Cardiology',
            'Neurology',
            'Orthopedics',
            'Pediatrics',
            'Gynecology',
            'Dermatology',
            'Psychiatry',
            'Emergency Medicine',
            'Internal Medicine',
            'Surgery',
            'Radiology',
        ];
    }
}