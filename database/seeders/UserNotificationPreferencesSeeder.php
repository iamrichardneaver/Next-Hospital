<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserNotificationPreference;

class UserNotificationPreferencesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default notification preferences for all existing users
        User::whereDoesntHave('notificationPreference')->each(function ($user) {
            UserNotificationPreference::create([
                'user_id' => $user->id,
                'audio_enabled' => true,
                'audio_volume' => 80,
                'notification_sound' => 'standard',
                'notify_opd_queue' => true,
                'notify_lab_queue' => true,
                'notify_pharmacy_queue' => true,
                'notify_emergency_queue' => true,
                'notify_triage_queue' => true,
                'notify_routine' => true,
                'notify_urgent' => true,
                'notify_critical' => true,
                'notify_new_patient' => true,
                'notify_patient_waiting' => true,
                'notify_prescription_ready' => true,
                'notify_lab_result_ready' => true,
                'notify_consultation_required' => true,
                'check_interval' => 30,
                'desktop_notification' => true,
                'do_not_disturb' => false,
            ]);
        });

        $this->command->info('Notification preferences created for all users.');
    }
}

