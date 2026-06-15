<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasIdPrefix;


class LabRequest extends Model
{
    use HasFactory, HasIdPrefix;

    /**
     * The field where the generated ID should be stored
     */
    protected $idField = 'lab_request_number';

    protected $fillable = [
        'id',
        'lab_request_number',
        'patient_id',
        'consultation_id',
        'doctor_id',
        'branch_id',
        'template_id',
        'test_type_id',
        'test_category_id',
        'test_category_name',
        'test_type_name',
        'request_number',
        'test_type',
        'test_description',
        'clinical_notes',
        'priority',
        'specimen_type',
        'collection_instructions',
        'special_instructions',
        'status',
        'technician_id',
        'collected_at',
        'completed_at',
        'inventory_deducted_at',
        'has_multiple_templates',
        'total_templates',
        'completed_templates',
        'overall_status',
        'created_by',
        'updated_by',
        'billing_status',
        'invoice_id',
        'billing_amount',
        'billed_at',
        'workflow_instance_id'
    ];

    /**
     * Get the entity type for ID generation
     */
    protected function getEntityType()
    {
        return 'lab_test';
    }

    protected $casts = [
        'collected_at' => 'datetime',
        'completed_at' => 'datetime',
        'inventory_deducted_at' => 'datetime',
        'has_multiple_templates' => 'boolean'
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($labRequest) {
            if (empty($labRequest->request_number)) {
                $labRequest->request_number = 'LAB-' . strtoupper(uniqid());
            }
        });
    }

    /**
     * Get the patient that owns this lab request.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the consultation that owns this lab request.
     */
    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Get the doctor who requested this lab test.
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the branch where this lab request was made.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the technician assigned to this lab request.
     */
    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * Get the user who created this lab request.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the lab results for this request.
     */
    public function results()
    {
        return $this->hasMany(LabTestResult::class, 'lab_request_id');
    }

    /**
     * Get the test type for this request.
     */
    public function testType()
    {
        return $this->belongsTo(LabTestType::class, 'test_type_id');
    }

    /**
     * Get the test category for this request.
     */
    public function testCategory()
    {
        return $this->belongsTo(LabTestCategory::class, 'test_category_id');
    }

    /**
     * Get the template for this request (legacy single template).
     */
    public function template()
    {
        return $this->belongsTo(LabTestTemplate::class, 'template_id');
    }

    /**
     * Get all templates assigned to this request.
     */
    public function templates()
    {
        return $this->belongsToMany(LabTestTemplate::class, 'lab_request_templates', 'lab_request_id', 'template_id')
                    ->withPivot(['status', 'assigned_technician_id', 'started_at', 'completed_at', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Get the template assignments for this request.
     */
    public function templateAssignments()
    {
        return $this->hasMany(LabRequestTemplate::class);
    }

    /**
     * Get the user who created this lab request.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this lab request.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the queues for this lab request.
     */
    public function queues()
    {
        return $this->hasMany(Queue::class, 'patient_id', 'patient_id')
            ->where('queue_type', 'Lab')
            ->whereColumn('queues.branch_id', 'lab_requests.branch_id');
    }

    /**
     * Scope a query to only include pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include in-progress requests.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope a query to only include completed requests.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include cancelled requests.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to filter by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to only include requests with multiple templates.
     */
    public function scopeWithMultipleTemplates($query)
    {
        return $query->where('has_multiple_templates', true);
    }

    /**
     * Scope a query to only include requests with single template.
     */
    public function scopeWithSingleTemplate($query)
    {
        return $query->where('has_multiple_templates', false);
    }

    /**
     * Scope a query to filter by overall status.
     */
    public function scopeByOverallStatus($query, $status)
    {
        return $query->where('overall_status', $status);
    }

    /**
     * Add templates to this request.
     */
    public function addTemplates(array $templateIds, $technicianId = null)
    {
        // Filter out null/empty values
        $templateIds = array_filter($templateIds, function($id) {
            return !empty($id);
        });
        
        if (empty($templateIds)) {
            return $this;
        }
        
        // Get existing template assignments to avoid duplicates
        $existingTemplateIds = $this->templates()->pluck('lab_test_templates.id')->toArray();
        
        $templates = [];
        foreach ($templateIds as $templateId) {
            // Skip if template already assigned
            if (in_array($templateId, $existingTemplateIds)) {
                continue;
            }
            
            $templates[$templateId] = [
                'assigned_technician_id' => $technicianId,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        // Only attach if there are new templates
        if (!empty($templates)) {
            $this->templates()->attach($templates);
        }
        
        // Update metadata
        $totalTemplates = $this->templates()->count();
        // Use wherePivot to query pivot table column
        $completedTemplates = $this->templates()->wherePivot('status', 'completed')->count();
        
        // Determine overall status
        $overallStatus = 'pending';
        if ($totalTemplates > 0) {
            if ($completedTemplates == $totalTemplates) {
                $overallStatus = 'completed';
            } elseif ($completedTemplates > 0) {
                $overallStatus = 'partial';
            }
        } else {
            $overallStatus = $this->overall_status ?? 'pending';
        }
        
        $this->update([
            'has_multiple_templates' => $totalTemplates > 1,
            'total_templates' => $totalTemplates,
            'completed_templates' => $completedTemplates,
            'overall_status' => $overallStatus
        ]);

        return $this;
    }

    /**
     * Update template completion status.
     */
    public function updateTemplateCompletion()
    {
        $completedCount = $this->templateAssignments()->where('status', 'completed')->count();
        $totalCount = $this->templateAssignments()->count();

        $overallStatus = 'pending';
        if ($completedCount === $totalCount && $totalCount > 0) {
            $overallStatus = 'completed';
        } elseif ($completedCount > 0) {
            $overallStatus = 'partial';
        }

        $this->update([
            'completed_templates' => $completedCount,
            'overall_status' => $overallStatus,
            'status' => $overallStatus === 'completed' ? 'completed' : 'in_progress'
        ]);

        return $this;
    }

    /**
     * Check if all templates are completed.
     */
    public function isFullyCompleted()
    {
        return $this->overall_status === 'completed';
    }

    /**
     * Check if request has multiple templates.
     */
    public function hasMultipleTemplates()
    {
        return $this->has_multiple_templates;
    }

    /**
     * Get completion percentage.
     */
    public function getCompletionPercentage()
    {
        if ($this->total_templates === 0) {
            return 0;
        }
        return round(($this->completed_templates / $this->total_templates) * 100, 2);
    }

    /**
     * Scope a query to filter by test type.
     */
    public function scopeByTestType($query, $testType)
    {
        return $query->where('test_type', $testType);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $dateFrom, $dateTo)
    {
        return $query->whereBetween('created_at', [$dateFrom, $dateTo]);
    }

    /**
     * Check if this request is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if this request is in progress.
     */
    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if this request is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if this request is cancelled.
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if this request is urgent.
     */
    public function isUrgent()
    {
        return $this->priority === 'urgent';
    }

    /**
     * Check if this request is STAT.
     */
    public function isStat()
    {
        return $this->priority === 'stat';
    }

    /**
     * Check if this request is routine.
     */
    public function isRoutine()
    {
        return $this->priority === 'routine';
    }

    /**
     * Get the turnaround time for this request.
     */
    public function getTurnaroundTime()
    {
        if (!$this->completed_at) {
            return null;
        }
        
        return $this->created_at->diffInHours($this->completed_at);
    }

    /**
     * Get the collection time for this request.
     */
    public function getCollectionTime()
    {
        if (!$this->collected_at) {
            return null;
        }
        
        return $this->created_at->diffInHours($this->collected_at);
    }

    /**
     * Get the processing time for this request.
     */
    public function getProcessingTime()
    {
        if (!$this->collected_at || !$this->completed_at) {
            return null;
        }
        
        return $this->collected_at->diffInHours($this->completed_at);
    }

    /**
     * Check if this request has critical results.
     */
    public function hasCriticalResults()
    {
        return $this->results()->where('result_status', 'critical')->exists();
    }

    /**
     * Check if this request has abnormal results.
     */
    public function hasAbnormalResults()
    {
        return $this->results()->where('result_status', 'abnormal')->exists();
    }

    /**
     * Get the critical results for this request.
     */
    public function getCriticalResults()
    {
        return $this->results()->where('result_status', 'critical')->get();
    }

    /**
     * Get the abnormal results for this request.
     */
    public function getAbnormalResults()
    {
        return $this->results()->where('result_status', 'abnormal')->get();
    }

    /**
     * Get the normal results for this request.
     */
    public function getNormalResults()
    {
        return $this->results()->where('result_status', 'normal')->get();
    }

    /**
     * Get the status badge class for display.
     */
    public function getStatusBadgeClass()
    {
        switch ($this->status) {
            case 'pending':
                return 'badge-warning';
            case 'in_progress':
                return 'badge-info';
            case 'completed':
                return 'badge-success';
            case 'cancelled':
                return 'badge-danger';
            default:
                return 'badge-secondary';
        }
    }

    /**
     * Get the priority badge class for display.
     */
    public function getPriorityBadgeClass()
    {
        switch ($this->priority) {
            case 'routine':
                return 'badge-secondary';
            case 'urgent':
                return 'badge-warning';
            case 'stat':
                return 'badge-danger';
            default:
                return 'badge-secondary';
        }
    }

    /**
     * Get the formatted request number.
     */
    public function getFormattedRequestNumber()
    {
        return 'LAB-' . $this->request_number;
    }

    /**
     * Get the age of the request in hours.
     */
    public function getAgeInHours()
    {
        return $this->created_at->diffInHours(now());
    }

    /**
     * Check if this request is overdue based on priority.
     */
    public function isOverdue()
    {
        $ageInHours = $this->getAgeInHours();
        
        switch ($this->priority) {
            case 'stat':
                return $ageInHours > 1; // STAT should be completed within 1 hour
            case 'urgent':
                return $ageInHours > 4; // Urgent should be completed within 4 hours
            case 'routine':
                return $ageInHours > 24; // Routine should be completed within 24 hours
            default:
                return false;
        }
    }
}