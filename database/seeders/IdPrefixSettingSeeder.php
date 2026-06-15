<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IdPrefixSetting;

class IdPrefixSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [
            [
                'entity_type' => 'patient',
                'company_prefix' => 'HWC',
                'module_prefix' => 'PAT',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'current_sequence' => 0,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'is_locked' => false,
                'is_active' => true,
                'description' => 'Patient ID format: HWC/PAT/25090600001'
            ],
            [
                'entity_type' => 'staff',
                'company_prefix' => 'HWC',
                'module_prefix' => 'STA',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'current_sequence' => 0,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'is_locked' => false,
                'is_active' => true,
                'description' => 'Staff ID format: HWC/STA/25090600001'
            ],
            [
                'entity_type' => 'doctor',
                'company_prefix' => 'HWC',
                'module_prefix' => 'DOC',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'current_sequence' => 0,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'is_locked' => false,
                'is_active' => true,
                'description' => 'Doctor ID format: HWC/DOC/25090600001'
            ],
            [
                'entity_type' => 'appointment',
                'company_prefix' => 'HWC',
                'module_prefix' => 'APT',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'current_sequence' => 0,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'is_locked' => false,
                'is_active' => true,
                'description' => 'Appointment ID format: HWC/APT/25090600001'
            ],
            [
                'entity_type' => 'consultation',
                'company_prefix' => 'HWC',
                'module_prefix' => 'CON',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'current_sequence' => 0,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'is_locked' => false,
                'is_active' => true,
                'description' => 'Consultation ID format: HWC/CON/25090600001'
            ],
            [
                'entity_type' => 'prescription',
                'company_prefix' => 'HWC',
                'module_prefix' => 'RX',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'current_sequence' => 0,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'is_locked' => false,
                'is_active' => true,
                'description' => 'Prescription ID format: HWC/RX/25090600001'
            ],
            [
                'entity_type' => 'lab_test',
                'company_prefix' => 'HWC',
                'module_prefix' => 'LAB',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'current_sequence' => 0,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'is_locked' => false,
                'is_active' => true,
                'description' => 'Lab Test ID format: HWC/LAB/25090600001'
            ],
            [
                'entity_type' => 'lab_result',
                'company_prefix' => 'HWC',
                'module_prefix' => 'RES',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'current_sequence' => 0,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'is_locked' => false,
                'is_active' => true,
                'description' => 'Lab Result ID format: HWC/RES/25090600001'
            ],
            [
                'entity_type' => 'invoice',
                'company_prefix' => 'HWC',
                'module_prefix' => 'INV',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'current_sequence' => 0,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'is_locked' => false,
                'is_active' => true,
                'description' => 'Invoice ID format: HWC/INV/25090600001'
            ],
            [
                'entity_type' => 'payment',
                'company_prefix' => 'HWC',
                'module_prefix' => 'PAY',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'current_sequence' => 0,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'is_locked' => false,
                'is_active' => true,
                'description' => 'Payment ID format: HWC/PAY/25090600001'
            ],
            [
                'entity_type' => 'drug',
                'company_prefix' => 'HWC',
                'module_prefix' => 'DRG',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'current_sequence' => 0,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'is_locked' => false,
                'is_active' => true,
                'description' => 'Drug ID format: HWC/DRG/25090600001'
            ],
            [
                'entity_type' => 'ward',
                'company_prefix' => 'HWC',
                'module_prefix' => 'WRD',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'current_sequence' => 0,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'is_locked' => false,
                'is_active' => true,
                'description' => 'Ward ID format: HWC/WRD/25090600001'
            ],
            [
                'entity_type' => 'bed',
                'company_prefix' => 'HWC',
                'module_prefix' => 'BED',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'current_sequence' => 0,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'is_locked' => false,
                'is_active' => true,
                'description' => 'Bed ID format: HWC/BED/25090600001'
            ],
            [
                'entity_type' => 'branch',
                'company_prefix' => 'HWC',
                'module_prefix' => 'BRN',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'current_sequence' => 0,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'is_locked' => false,
                'is_active' => true,
                'description' => 'Branch ID format: HWC/BRN/25090600001'
            ],
        ];

        foreach ($defaultSettings as $setting) {
            IdPrefixSetting::updateOrCreate(
                ['entity_type' => $setting['entity_type']],
                $setting
            );
        }
    }
}
