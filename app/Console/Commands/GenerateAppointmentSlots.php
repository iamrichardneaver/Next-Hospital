<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DoctorSchedule;
use App\Models\AppointmentSlot;
use App\Models\User;
use App\Models\Branch;
use Carbon\Carbon;

class GenerateAppointmentSlots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:generate-slots 
                            {--doctor= : Specific doctor ID}
                            {--branch= : Specific branch ID}
                            {--days=30 : Number of days ahead to generate slots}
                            {--type=both : Appointment type (in-person, teleconsultation, or both)}
                            {--fee= : Optional consultation fee}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate appointment slots from doctor schedules';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🏥 Generating Appointment Slots...');
        $this->newLine();

        // Get parameters
        $daysAhead = (int) $this->option('days');
        $doctorId = $this->option('doctor');
        $branchId = $this->option('branch');
        $appointmentTypes = $this->getAppointmentTypes();
        $fee = $this->option('fee');

        // Date range
        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->addDays($daysAhead)->endOfDay();

        $this->info("📅 Generating slots from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
        $this->newLine();

        // Get doctor schedules
        $query = DoctorSchedule::with(['doctor', 'branch'])
            ->effective()
            ->available();

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->error('❌ No doctor schedules found. Please create doctor schedules first.');
            $this->newLine();
            $this->info('Run: php artisan db:seed --class=DoctorScheduleSeeder');
            return 1;
        }

        $this->info("Found {$schedules->count()} doctor schedule(s)");
        $this->newLine();

        $totalCreated = 0;
        $totalSkipped = 0;

        $progressBar = $this->output->createProgressBar($daysAhead);
        $progressBar->start();

        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dayOfWeek = strtolower($currentDate->format('l'));

            // Get schedules for this day
            $daySchedules = $schedules->where('day_of_week', $dayOfWeek);

            foreach ($daySchedules as $schedule) {
                foreach ($appointmentTypes as $type) {
                    $slots = $schedule->getAvailableSlots($currentDate);

                    foreach ($slots as $slotData) {
                        // Check if slot already exists
                        $existingSlot = AppointmentSlot::where('doctor_id', $schedule->doctor_id)
                            ->where('branch_id', $schedule->branch_id)
                            ->where('slot_date', $currentDate->format('Y-m-d'))
                            ->where('start_time', $slotData['start_time'])
                            ->where('appointment_type', $type)
                            ->first();

                        if ($existingSlot) {
                            $totalSkipped++;
                            continue;
                        }

                        // Create appointment slot
                        AppointmentSlot::create([
                            'doctor_id' => $schedule->doctor_id,
                            'branch_id' => $schedule->branch_id,
                            'slot_date' => $currentDate->format('Y-m-d'),
                            'start_time' => $slotData['start_time'],
                            'end_time' => $slotData['end_time'],
                            'duration' => $slotData['duration'],
                            'max_appointments' => $slotData['max_appointments'],
                            'booked_appointments' => 0,
                            'appointment_type' => $type,
                            'fee' => $fee ?? $slotData['fee'] ?? 0,
                            'currency' => 'GHS',
                            'status' => 'available',
                            'notes' => 'Generated automatically',
                            'created_by' => 1, // System
                        ]);

                        $totalCreated++;
                    }
                }
            }

            $currentDate->addDay();
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('✅ Appointment Slots Generation Complete!');
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Slots Created', $totalCreated],
                ['Slots Skipped (Already Exist)', $totalSkipped],
                ['Total Processed', $totalCreated + $totalSkipped],
            ]
        );

        $this->newLine();
        $this->info('💡 Tip: You can now book appointments through the web or mobile app!');
        
        return 0;
    }

    /**
     * Get appointment types based on the type option.
     *
     * @return array
     */
    private function getAppointmentTypes()
    {
        $type = $this->option('type');

        return match($type) {
            'in-person' => ['in-person'],
            'teleconsultation' => ['teleconsultation'],
            'both' => ['in-person', 'teleconsultation'],
            default => ['in-person', 'teleconsultation'],
        };
    }
}

