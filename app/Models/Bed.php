<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\HasIdPrefix;

class Bed extends Model
{
    use HasFactory, HasIdPrefix;
    protected $fillable = [
        'id',
        'ward_id',
        'bed_number',
        'bed_type',
        'status',
        'is_active'
    ];

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        // Don't generate ID prefix for primary key, use integer IDs
        return null;
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($bed) {
            // Set bed_number to the generated ID
            if (empty($bed->bed_number)) {
                $bed->bed_number = $bed->id;
            }
        });
    }

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Get the ward that owns the bed.
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * Get the current patient assigned to the bed (via active assignment).
     */
    public function patient()
    {
        return $this->hasOneThrough(
            Patient::class,
            BedAssignment::class,
            'bed_id',
            'id',
            'id',
            'patient_id'
        )->where('bed_assignments.status', 'active');
    }

    /**
     * Get the bed assignments for the bed.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(BedAssignment::class);
    }

    /**
     * Get the current active assignment for the bed.
     */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(BedAssignment::class)
            ->whereNull('discharge_date')
            ->where('status', 'active')
            ->latest();
    }

}
