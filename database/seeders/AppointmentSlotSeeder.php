<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AppointmentSlot;
use App\Models\DoctorSchedule;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentSlotSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get all active doctors
        $doctors = User::role('doctor')
            ->where('is_active', true)
            ->with('staffProfile')
            ->get();

        if ($doctors->isEmpty()) {
            $this->command->warn('No active doctors found. Skipping appointment slot generation.');
            return;
        }

        // Get default branch or first branch
        $defaultBranch = Branch::first();
        if (!$defaultBranch) {
            $this->command->warn('No branch found. Cannot generate appointment slots.');
            return;
        }

        $this->command->info('Generating appointment slots for ' . $doctors->count() . ' doctor(s)...');

        $totalSlotsCreated = 0;

        foreach ($doctors as $doctor) {
            $branchId = $doctor->staffProfile?->branch_id ?? $defaultBranch->id;
            
            // Check if doctor has schedules
            $schedules = DoctorSchedule::where('doctor_id', $doctor->id)
                ->where('branch_id', $branchId)
                ->where('is_available', true)
                ->effective()
                ->get();

            if ($schedules->isEmpty()) {
                // Create default schedule for doctors without one
                $this->createDefaultSchedule($doctor->id, $branchId);
                $schedules = DoctorSchedule::where('doctor_id', $doctor->id)
                    ->where('branch_id', $branchId)
                    ->where('is_available', true)
                    ->effective()
                    ->get();
            }

            // Generate slots for next 30 days
            $startDate = now();
            $endDate = now()->addDays(30);
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                $dayOfWeek = strtolower($currentDate->format('l'));
                
                // Find schedule for this day
                $daySchedule = $schedules->where('day_of_week', $dayOfWeek)->first();

                if ($daySchedule) {
                    // Generate slots for this day
                    $slotsCreated = $this->generateSlotsForDay($doctor->id, $branchId, $currentDate, $daySchedule);
                    $totalSlotsCreated += $slotsCreated;
                }

                $currentDate->addDay();
            }

            $this->command->info("Generated slots for Dr. {$doctor->name}");
        }

        $this->command->info("Total slots created: {$totalSlotsCreated}");
    }

    /**
     * Create default schedule for a doctor
     */
    private function createDefaultSchedule($doctorId, $branchId): void
    {
        $workingDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        
        foreach ($workingDays as $day) {
            DoctorSchedule::create([
                'doctor_id' => $doctorId,
                'branch_id' => $branchId,
                'day_of_week' => $day,
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_start_time' => '12:00:00',
                'break_end_time' => '13:00:00',
                'slot_duration' => 30,
                'max_appointments_per_slot' => 1,
                'is_available' => true,
                'notes' => 'Default schedule',
                'created_by' => $doctorId,
            ]);
        }

        $this->command->info("Created default schedule for doctor ID: {$doctorId}");
    }

    /**
     * Generate appointment slots for a specific day
     */
    private function generateSlotsForDay($doctorId, $branchId, Carbon $date, DoctorSchedule $schedule): int
    {
        $slotsCreated = 0;
        $dateString = $date->format('Y-m-d');

        // Check if slots already exist for this date
        $existingSlots = AppointmentSlot::where('doctor_id', $doctorId)
            ->where('branch_id', $branchId)
            ->where('slot_date', $dateString)
            ->count();

        if ($existingSlots > 0) {
            return 0; // Skip if slots already exist
        }

        // Get available time slots from schedule
        $timeSlots = $schedule->getAvailableSlots($date);

        foreach ($timeSlots as $slotData) {
            // Create both in-person and teleconsultation slots
            foreach (['in-person', 'teleconsultation'] as $appointmentType) {
                try {
                    AppointmentSlot::create([
                        'doctor_id' => $doctorId,
                        'branch_id' => $branchId,
                        'slot_date' => $dateString,
                        'start_time' => $slotData['start_time'],
                        'end_time' => $slotData['end_time'],
                        'duration' => $slotData['duration'],
                        'max_appointments' => $slotData['max_appointments'],
                        'booked_appointments' => 0,
                        'status' => 'available',
                        'fee' => null, // Will be fetched from appointment_fees if exists
                        'currency' => 'GHS',
                        'appointment_type' => $appointmentType,
                        'notes' => 'Auto-generated slot',
                        'created_by' => $doctorId,
                    ]);
                    $slotsCreated++;
                } catch (\Exception $e) {
                    $this->command->error("Error creating slot: " . $e->getMessage());
                }
            }
        }

        return $slotsCreated;
    }
}

