<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class DoctorSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'branch_id',
        'day_of_week',
        'start_time',
        'end_time',
        'break_start_time',
        'break_end_time',
        'slot_duration',
        'max_appointments_per_slot',
        'is_available',
        'notes',
        'effective_from',
        'effective_until',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'break_start_time' => 'datetime:H:i',
        'break_end_time' => 'datetime:H:i',
        'is_available' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    /**
     * Get the doctor that owns the schedule.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the branch that owns the schedule.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the schedule.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the schedule.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if schedule is currently effective.
     */
    public function isEffective(): bool
    {
        $now = now()->toDateString();
        
        if ($this->effective_from && $this->effective_from > $now) {
            return false;
        }
        
        if ($this->effective_until && $this->effective_until < $now) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if schedule is available for a specific date.
     */
    public function isAvailableForDate(Carbon $date): bool
    {
        if (!$this->is_available || !$this->isEffective()) {
            return false;
        }

        $dayOfWeek = strtolower($date->format('l'));
        return $this->day_of_week === $dayOfWeek;
    }

    /**
     * Get available time slots for a specific date.
     */
    public function getAvailableSlots(Carbon $date): array
    {
        if (!$this->isAvailableForDate($date)) {
            return [];
        }

        $slots = [];
        $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $this->start_time->format('H:i'));
        $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $this->end_time->format('H:i'));
        $breakStart = $this->break_start_time ? Carbon::parse($date->format('Y-m-d') . ' ' . $this->break_start_time->format('H:i')) : null;
        $breakEnd = $this->break_end_time ? Carbon::parse($date->format('Y-m-d') . ' ' . $this->break_end_time->format('H:i')) : null;

        while ($startTime->lt($endTime)) {
            $slotEnd = $startTime->copy()->addMinutes($this->slot_duration);
            
            // Skip if slot would go beyond end time
            if ($slotEnd->gt($endTime)) {
                break;
            }

            // Skip if slot is during break time
            if ($breakStart && $breakEnd && 
                ($startTime->between($breakStart, $breakEnd) || $slotEnd->between($breakStart, $breakEnd))) {
                $startTime->addMinutes($this->slot_duration);
                continue;
            }

            $slots[] = [
                'start_time' => $startTime->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
                'duration' => $this->slot_duration,
                'max_appointments' => $this->max_appointments_per_slot,
            ];

            $startTime->addMinutes($this->slot_duration);
        }

        return $slots;
    }

    /**
     * Scope to get schedules for a specific doctor.
     */
    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope to get schedules for a specific branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to get schedules for a specific day.
     */
    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope to get available schedules.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope to get effective schedules.
     */
    public function scopeEffective($query)
    {
        $now = now()->toDateString();
        return $query->where(function($q) use ($now) {
            $q->whereNull('effective_from')
              ->orWhere('effective_from', '<=', $now);
        })->where(function($q) use ($now) {
            $q->whereNull('effective_until')
              ->orWhere('effective_until', '>=', $now);

        });
    }
}
