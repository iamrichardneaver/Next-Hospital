<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyScheduleSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_id',
        'slot_date',
        'start_time',
        'end_time',
        'status',
        'booked_by',
        'study_id',
        'notes'
    ];

    protected $casts = [
        'slot_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i'
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(RadiologyEquipment::class, 'equipment_id');
    }

    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function study(): BelongsTo
    {
        return $this->belongsTo(RadiologyStudy::class, 'study_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isBooked(): bool
    {
        return $this->status === 'booked';
    }

    public function getDuration(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }
}
