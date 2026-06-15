<?php

namespace App\Models;

use App\Traits\HasIdPrefix;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Queue extends Model
{
    use HasFactory;


    protected $entityType = 'queue';
    protected $idField = 'ticket_number';

    protected $fillable = [
        'visit_id',
        'patient_id',
        'branch_id',
        'queue_type',
        'ticket_number',
        'position',
        'status',
        'queued_at',
        'called_at',
        'serving_at',
        'completed_at',
        'called_by',
        'served_by',
        'notes',
        'estimated_wait_time',
        'priority',
    ];

    protected $casts = [
        'queued_at' => 'datetime',
        'called_at' => 'datetime',
        'serving_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($queue) {
            if (empty($queue->ticket_number)) {
                $queue->ticket_number = self::generateTicketNumber($queue->queue_type, $queue->branch_id);
            }
        });
    }

    /**
     * Get the visit that owns the queue.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * Get the patient that owns the queue.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the branch that owns the queue.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who called the patient.
     */
    public function calledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'called_by');
    }

    /**
     * Get the user who served the patient.
     */
    public function servedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'served_by');
    }

    /**
     * Get lab requests for this queue entry.
     * Note: When eager loading, the closure in the service will filter by branch_id.
     */
    public function labRequests(): HasMany
    {
        return $this->hasMany(LabRequest::class, 'patient_id', 'patient_id');
    }

    /**
     * Check if patient is waiting.
     */
    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }

    /**
     * Check if patient is called.
     */
    public function isCalled(): bool
    {
        return $this->status === 'called';
    }

    /**
     * Check if patient is being served.
     */
    public function isServing(): bool
    {
        return $this->status === 'serving';
    }

    /**
     * Check if patient is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get wait time in minutes.
     */
    public function getWaitTimeInMinutes(): ?int
    {
        if (!$this->called_at) {
            return $this->queued_at->diffInMinutes(now());
        }
        
        return $this->queued_at->diffInMinutes($this->called_at);
    }

    /**
     * Get service time in minutes.
     */
    public function getServiceTimeInMinutes(): ?int
    {
        if (!$this->serving_at || !$this->completed_at) {
            return null;
        }
        
        return $this->serving_at->diffInMinutes($this->completed_at);
    }

    /**
     * Scope to get queues by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('queue_type', $type);
    }

    /**
     * Scope to get waiting queues.
     */
    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    /**
     * Scope to get called queues.
     */
    public function scopeCalled($query)
    {
        return $query->where('status', 'called');
    }

    /**
     * Scope to get serving queues.
     */
    public function scopeServing($query)
    {
        return $query->where('status', 'serving');
    }

    /**
     * Scope to get completed queues.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get queues by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Order queues: critical first, then urgent, then routine.
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'urgent' THEN 2 ELSE 3 END");
    }

    /**
     * Scope to get queues for a specific branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Generate unique ticket number for queue.
     * Format: [QUEUE_TYPE_PREFIX][BRANCH_ID]-[YYYYMMDD]-[SEQUENCE]
     * Example: OPD1-20251001-001, LAB2-20251001-045
     */
    public static function generateTicketNumber(string $queueType, int $branchId): string
    {
        $prefix = strtoupper(substr($queueType, 0, 3)); // OPD, LAB, PHA, EME, RAD
        $date = now()->format('Ymd');
        
        // Get last ticket number for today
        $lastTicket = self::where('queue_type', $queueType)
            ->where('branch_id', $branchId)
            ->whereDate('queued_at', now()->toDateString())
            ->orderBy('id', 'desc')
            ->value('ticket_number');
        
        $sequence = 1;
        
        if ($lastTicket) {
            // Extract sequence from last ticket (format: XXX#-YYYYMMDD-###)
            $parts = explode('-', $lastTicket);
            if (count($parts) === 3) {
                $sequence = intval($parts[2]) + 1;
            }
        }
        
        return sprintf('%s%d-%s-%03d', $prefix, $branchId, $date, $sequence);
    }

    /**
     * Get display-friendly ticket number (just the sequence part for display board).
     */
    public function getShortTicketAttribute(): string
    {
        if (!$this->ticket_number) {
            return 'N/A';
        }
        
        $parts = explode('-', $this->ticket_number);
        return count($parts) === 3 ? $parts[2] : $this->ticket_number;
    }
}
