<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurgeryProcedure extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'procedure_type',
        'category',
        'duration_minutes',
        'anesthesia_type',
        'complexity_level',
        'equipment_required',
        'pre_op_requirements',
        'post_op_care',
        'is_active'
    ];

    protected $casts = [
        'equipment_required' => 'array',
        'pre_op_requirements' => 'array',
        'is_active' => 'boolean'
    ];

    public function surgerySchedules()
    {
        return $this->hasMany(SurgerySchedule::class);
    }
}
