<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IdPrefixSetting;
use App\Services\IdPrefixService;

class ProductionIdPrefixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = new IdPrefixService();
        
        // Define all required entity types with their configurations
        $entityConfigs = [
            'patient' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'PAT',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}/{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'description' => 'Patient ID Pattern'
            ],
            'staff' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'STF',
                'pattern' => '{company_prefix}/{module_prefix}/{sequence}',
                'sequence_length' => 4,
                'include_year' => false,
                'include_month' => false,
                'include_day' => false,
                'description' => 'Staff ID Pattern'
            ],
            'doctor' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'DOC',
                'pattern' => '{company_prefix}/{module_prefix}/{sequence}',
                'sequence_length' => 4,
                'include_year' => false,
                'include_month' => false,
                'include_day' => false,
                'description' => 'Doctor ID Pattern'
            ],
            'appointment' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'APT',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}/{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'description' => 'Appointment ID Pattern'
            ],
            'consultation' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'CON',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}/{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'description' => 'Consultation ID Pattern'
            ],
            'prescription' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'PRS',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}/{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'description' => 'Prescription ID Pattern'
            ],
            'lab_test' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'LAB',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}/{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'description' => 'Lab Test ID Pattern'
            ],
            'lab_result' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'LRS',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}/{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'description' => 'Lab Result ID Pattern'
            ],
            'invoice' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'INV',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}/{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'description' => 'Invoice ID Pattern'
            ],
            'payment' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'PAY',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}/{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'description' => 'Payment ID Pattern'
            ],
            'drug' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'DRG',
                'pattern' => '{company_prefix}/{module_prefix}/{sequence}',
                'sequence_length' => 4,
                'include_year' => false,
                'include_month' => false,
                'include_day' => false,
                'description' => 'Drug ID Pattern'
            ],
            'ward' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'WRD',
                'pattern' => '{company_prefix}/{module_prefix}/{sequence}',
                'sequence_length' => 3,
                'include_year' => false,
                'include_month' => false,
                'include_day' => false,
                'description' => 'Ward ID Pattern'
            ],
            'bed' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'BED',
                'pattern' => '{company_prefix}/{module_prefix}/{sequence}',
                'sequence_length' => 4,
                'include_year' => false,
                'include_month' => false,
                'include_day' => false,
                'description' => 'Bed ID Pattern'
            ],
            'branch' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'BRN',
                'pattern' => '{company_prefix}/{module_prefix}/{sequence}',
                'sequence_length' => 3,
                'include_year' => false,
                'include_month' => false,
                'include_day' => false,
                'description' => 'Branch ID Pattern'
            ],
            'visit' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'VST',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'description' => 'Visit ID Pattern'
            ],
            'queue' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'QUE',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}/{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'description' => 'Queue ID Pattern'
            ],
            'emergency_visit' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'EMV',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'description' => 'Emergency Visit ID Pattern'
            ],
            'emergencyalert' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'EAL',
                'pattern' => '{company_prefix}-{module_prefix}-{sequence}',
                'sequence_length' => 4,
                'include_year' => false,
                'include_month' => false,
                'include_day' => false,
                'description' => 'Emergency Alert ID Pattern'
            ],
            'teleconsultation' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'TEL',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'description' => 'Teleconsultation ID Pattern'
            ],
            'complaint' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'CMP',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}/{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'description' => 'Complaint ID Pattern'
            ]
        ];

        foreach ($entityConfigs as $entityType => $config) {
            try {
                $service->getOrCreateSetting($entityType, $config);
                $this->command->info("✓ ID prefix setting created/updated for: {$entityType}");
            } catch (\Exception $e) {
                $this->command->error("✗ Failed to create ID prefix setting for {$entityType}: " . $e->getMessage());
            }
        }

        $this->command->info('ID prefix settings seeding completed!');
    }
}
