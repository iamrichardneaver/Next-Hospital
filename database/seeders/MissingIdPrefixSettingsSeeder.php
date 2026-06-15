<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IdPrefixSetting;
use App\Services\IdPrefixService;

class MissingIdPrefixSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $idPrefixService = app(IdPrefixService::class);
        
        // Define missing entity types with their default settings
        $missingEntityTypes = [
            'lab_result' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'LR',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for lab results'
            ],
            'note' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'NOT',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for consultation notes'
            ],
            'insurance_claim' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'CLM',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for insurance claims'
            ],
            'store_item' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'ITM',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for store items'
            ],
            'bed_assignment' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'BED',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for bed assignments'
            ],
            'vital' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'VIT',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for vital signs'
            ],
            'diagnosis' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'DIA',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for diagnoses'
            ],
            'consultation_intervention' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'INT',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for consultation interventions'
            ],
            'follow_up' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'FLU',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for follow-ups'
            ],
            'referral' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'REF',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for referrals'
            ],
            'insurance_policy' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'POL',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for insurance policies'
            ],
            'insurance_provider' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'PRV',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for insurance providers'
            ],
            'radiology_request' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'RAD',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for radiology requests'
            ],
            'radiology_report' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'RPT',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for radiology reports'
            ],
            'surgery_schedule' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'SUR',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for surgery schedules'
            ],
            'teleconsultation' => [
                'company_prefix' => 'HWC',
                'module_prefix' => 'TEL',
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
                'sequence_length' => 5,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'description' => 'ID pattern for teleconsultations'
            ]
        ];
        
        foreach ($missingEntityTypes as $entityType => $defaultData) {
            // Check if setting already exists
            $existing = IdPrefixSetting::where('entity_type', $entityType)->first();
            
            if (!$existing) {
                try {
                    $idPrefixService->getOrCreateSetting($entityType, $defaultData);
                    $this->command->info("Created ID prefix setting for: {$entityType}");
                } catch (\Exception $e) {
                    $this->command->error("Failed to create ID prefix setting for {$entityType}: " . $e->getMessage());
                }
            } else {
                $this->command->info("ID prefix setting already exists for: {$entityType}");
            }
        }
    }
}