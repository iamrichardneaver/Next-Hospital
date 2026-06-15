<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabRequestTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_request_id',
        'template_id',
        'status',
        'assigned_technician_id',
        'started_at',
        'completed_at',
        'notes'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the lab request that owns this template assignment.
     */
    public function labRequest()
    {
        return $this->belongsTo(LabRequest::class);
    }

    /**
     * Get the template for this assignment.
     */
    public function template()
    {
        return $this->belongsTo(LabTestTemplate::class);
    }

    /**
     * Get the assigned technician.
     */
    public function assignedTechnician()
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    /**
     * Get the test results for this template assignment.
     */
    public function testResults()
    {
        return $this->hasMany(LabTestResult::class, 'lab_request_id', 'lab_request_id')
                    ->where('template_id', $this->template_id);
    }

    /**
     * Scope a query to only include pending assignments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include in-progress assignments.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope a query to only include completed assignments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include cancelled assignments.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Mark this template assignment as started.
     */
    public function markAsStarted($technicianId = null)
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'assigned_technician_id' => $technicianId ?? auth()->id()
        ]);
    }

    /**
     * Mark this template assignment as completed.
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    /**
     * Check if this template assignment is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if this template assignment is in progress.
     */
    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if this template assignment is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }
}
