<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AppointmentSlot extends Model
{
    use HasFactory;


    protected $fillable = [
        'doctor_id',
        'branch_id',
        'slot_date',
        'start_time',
        'end_time',
        'duration',
        'max_appointments',
        'booked_appointments',
        'status',
        'fee',
        'currency',
        'appointment_type',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'slot_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'fee' => 'decimal:2',
    ];

    /**
     * Get the doctor that owns the slot.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the branch that owns the slot.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the slot.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the slot.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if slot is available for booking.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available' && 
               $this->booked_appointments < $this->max_appointments;
    }

    /**
     * Check if slot is fully booked.
     */
    public function isFullyBooked(): bool
    {
        return $this->booked_appointments >= $this->max_appointments;
    }

    /**
     * Check if slot is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    /**
     * Check if slot is in maintenance.
     */
    public function isInMaintenance(): bool
    {
        return $this->status === 'maintenance';
    }

    /**
     * Book an appointment in this slot.
     */
    public function bookAppointment(): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $this->increment('booked_appointments');
        
        if ($this->booked_appointments >= $this->max_appointments) {
            $this->update(['status' => 'booked']);
        }

        return true;
    }

    /**
     * Cancel an appointment in this slot.
     */
    public function cancelAppointment(): bool
    {
        if ($this->booked_appointments <= 0) {
            return false;
        }

        $this->decrement('booked_appointments');
        
        if ($this->status === 'booked' && $this->booked_appointments < $this->max_appointments) {
            $this->update(['status' => 'available']);
        }

        return true;
    }

    /**
     * Get remaining capacity.
     */
    public function getRemainingCapacity(): int
    {
        return max(0, $this->max_appointments - $this->booked_appointments);
    }

    /**
     * Check if slot is in the past.
     */
    public function isPast(): bool
    {
        $slotDateTime = Carbon::parse($this->slot_date->format('Y-m-d') . ' ' . $this->start_time->format('H:i:s'));
        return $slotDateTime->isPast();
    }

    /**
     * Check if slot is today.
     */
    public function isToday(): bool
    {
        return $this->slot_date->isToday();
    }

    /**
     * Check if slot is in the future.
     */
    public function isFuture(): bool
    {
        $slotDateTime = Carbon::parse($this->slot_date->format('Y-m-d') . ' ' . $this->start_time->format('H:i:s'));
        return $slotDateTime->isFuture();
    }

    /**
     * Scope to get slots for a specific doctor.
     */
    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope to get slots for a specific branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to get slots for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('slot_date', $date);
    }

    /**
     * Scope to get available slots.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
                    ->whereColumn('booked_appointments', '<', 'max_appointments');
    }

    /**
     * Scope to get slots by appointment type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('appointment_type', $type);
    }

    /**
     * Scope to get future slots.
     */
    public function scopeFuture($query)
    {
        return $query->where(function($q) {
            $q->where('slot_date', '>', now()->toDateString())
              ->orWhere(function($subQ) {
                  $subQ->where('slot_date', now()->toDateString())
                       ->where('start_time', '>', now()->format('H:i:s'));
              });
        });
    }

    /**
     * Scope to get slots within date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('slot_date', [$startDate, $endDate]);
    }
}
