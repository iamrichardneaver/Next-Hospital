<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasIdPrefix;

class Complaint extends Model
{
    use HasFactory, HasIdPrefix;


    protected $fillable = [
        'id',
        'complaint_number',
        'patient_id',
        'branch_id',
        'complainant_name',
        'complainant_phone',
        'complainant_email',
        'complainant_type',
        'subject',
        'description',
        'category',
        'severity',
        'status',
        'priority',
        'assigned_to',
        'response',
        'resolution_notes',
        'resolved_at',
        'resolved_by',
        'attachments',
        'requires_follow_up',
        'follow_up_date',
        'follow_up_notes',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        return null; // Don't generate ID prefix for primary key
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($complaint) {
            // Generate complaint_number using ID prefix service
            if (empty($complaint->complaint_number)) {
                $complaint->complaint_number = app(\App\Services\IdPrefixService::class)->generateId('complaint');
            }
        });
    }

    protected $casts = [
        'resolved_at' => 'datetime',
        'follow_up_date' => 'date',
        'attachments' => 'array',
        'requires_follow_up' => 'boolean',
    ];

    /**
     * Get the patient that owns the complaint.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the branch that owns the complaint.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user assigned to the complaint.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who resolved the complaint.
     */
    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the user who created the complaint.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the complaint.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get complaints by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending complaints.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get resolved complaints.
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope to get complaints by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get complaints by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get complaints by severity.
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Check if complaint is resolved
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Check if complaint is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}

