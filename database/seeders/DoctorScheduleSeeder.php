<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DoctorSchedule;
use App\Models\User;
use App\Models\Branch;
use Carbon\Carbon;

class DoctorScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get all doctors
        $doctors = User::role('doctor')->get();
        
        // Get all branches
        $branches = Branch::all();

        if ($doctors->isEmpty()) {
            $this->command->warn('No doctors found in the system. Please create doctors first.');
            return;
        }

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found in the system. Please create branches first.');
            return;
        }

        $this->command->info('Creating doctor schedules...');

        // Days of the week
        $weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        
        // Default working hours
        $morningStart = '08:00';
        $morningEnd = '12:00';
        $afternoonStart = '14:00';
        $afternoonEnd = '18:00';
        $breakStart = '12:00';
        $breakEnd = '14:00';

        $createdCount = 0;

        foreach ($doctors as $doctor) {
            // Assign each doctor to first branch by default (you can customize this)
            $branch = $branches->first();

            $this->command->info("Creating schedule for Dr. {$doctor->name} at {$branch->name}");

            foreach ($weekDays as $dayOfWeek) {
                // Check if schedule already exists
                $existingSchedule = DoctorSchedule::where('doctor_id', $doctor->id)
                    ->where('branch_id', $branch->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->first();

                if ($existingSchedule) {
                    $this->command->warn("  Schedule for {$dayOfWeek} already exists. Skipping.");
                    continue;
                }

                // Create schedule
                DoctorSchedule::create([
                    'doctor_id' => $doctor->id,
                    'branch_id' => $branch->id,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $morningStart,
                    'end_time' => $afternoonEnd,
                    'break_start_time' => $breakStart,
                    'break_end_time' => $breakEnd,
                    'slot_duration' => 30, // 30 minutes per appointment
                    'max_appointments_per_slot' => 1, // 1 patient per slot
                    'is_available' => true,
                    'effective_from' => Carbon::now()->startOfMonth(),
                    'effective_until' => null, // No end date - schedule is ongoing
                    'notes' => 'Default schedule created by seeder',
                    'created_by' => 1, // Assuming admin user ID is 1
                ]);

                $createdCount++;
                $this->command->info("  ✓ Created schedule for {$dayOfWeek}");
            }
        }

        $this->command->info("Successfully created {$createdCount} doctor schedules.");
        $this->command->info('');
        $this->command->info('Next step: Run the appointment slot generator:');
        $this->command->info('  php artisan appointments:generate-slots');
    }
}

