<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasIdPrefix;

class Scan extends Model
{
    use HasFactory, HasIdPrefix;

    protected $fillable = [
        'id',
        'scan_number',
        'patient_id',
        'consultation_id',
        'doctor_id',
        'branch_id',
        'scan_type',
        'scan_date',
        'scan_time',
        'scan_description',
        'scan_results',
        'scan_images',
        'status',
        'technician_id',
        'completed_at',
        'created_by',
        'updated_by'
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
        
        static::creating(function ($scan) {
            // Generate scan_number using ID prefix service
            if (empty($scan->scan_number)) {
                $scan->scan_number = app(\App\Services\IdPrefixService::class)->generateId('scan');
            }
        });
    }

    protected $casts = [
        'scan_date' => 'date',
        'scan_time' => 'datetime:H:i',
        'scan_results' => 'array',
        'scan_images' => 'array',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the patient that owns this scan.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the consultation that owns this scan.
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Get the doctor that owns this scan.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the technician that performed this scan.
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * Get the branch that owns this scan.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
