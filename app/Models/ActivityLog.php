<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'event',
        'batch_uuid'
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the subject of the activity.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the causer of the activity.
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get logs by log name.
     */
    public function scopeLogName($query, $logName)
    {
        return $query->where('log_name', $logName);
    }

    /**
     * Scope to get logs by event.
     */
    public function scopeEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to get logs by causer.
     */
    public function scopeCauser($query, $causerType, $causerId = null)
    {
        $query = $query->where('causer_type', $causerType);
        
        if ($causerId) {
            $query->where('causer_id', $causerId);
        }
        
        return $query;
    }

    /**
     * Scope to get logs by subject.
     */
    public function scopeSubject($query, $subjectType, $subjectId = null)
    {
        $query = $query->where('subject_type', $subjectType);
        
        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }
        
        return $query;
    }

    /**
     * Scope to get logs by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get formatted description.
     */
    public function getFormattedDescription()
    {
        return $this->description;
    }

    /**
     * Get causer name.
     */
    public function getCauserName()
    {
        if ($this->causer_type === 'App\\Models\\User' && $this->causer) {
            return $this->causer->name ?? 'Unknown User';
        }
        
        return 'System';
    }

    /**
     * Get subject name.
     */
    public function getSubjectName()
    {
        if ($this->subject_type && $this->subject) {
            switch ($this->subject_type) {
                case 'App\\Models\\Invoice':
                    return 'Invoice #' . ($this->subject->invoice_number ?? $this->subject->id);
                case 'App\\Models\\Payment':
                    return 'Payment #' . ($this->subject->payment_reference ?? $this->subject->id);
                case 'App\\Models\\Patient':
                    return 'Patient: ' . ($this->subject->first_name ?? '') . ' ' . ($this->subject->last_name ?? '');
                default:
                    return class_basename($this->subject_type) . ' #' . $this->subject_id;
            }
        }
        
        return 'Unknown';
    }
}
