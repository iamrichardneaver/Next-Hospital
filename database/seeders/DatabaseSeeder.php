<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Auto-generated from live nexthospital database schema.
 * Generated: 2026-06-15 14:19:56
 *
 * Test on a SEPARATE database only (e.g. nexthospital_schema_test):
 *   PERMISSIONS_AUTO_SYNC=false DB_DATABASE=nexthospital_schema_test php artisan migrate --seed
 *
 * Set PERMISSIONS_AUTO_SYNC=false — otherwise boot-time permission sync overwrites
 * Generated permission rows before PermissionsSeeder runs (ID mismatch).
 *
 * NEVER run migrate:fresh on the operational nexthospital database.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \Database\Seeders\Generated\ActivityLogsSeeder::class,
            \Database\Seeders\Generated\ApiSettingsSeeder::class,
            \Database\Seeders\Generated\AppVersionsSeeder::class,
            \Database\Seeders\Generated\AppointmentFeesSeeder::class,
            \Database\Seeders\Generated\BranchesSeeder::class,
            \Database\Seeders\Generated\BrandingSettingsSeeder::class,
            \Database\Seeders\Generated\CacheSeeder::class,
            \Database\Seeders\Generated\ConsultationTemplatesSeeder::class,
            \Database\Seeders\Generated\ContrastAgentsSeeder::class,
            \Database\Seeders\Generated\DocumentSettingsSeeder::class,
            \Database\Seeders\Generated\DrugsSeeder::class,
            \Database\Seeders\Generated\EmailSettingsSeeder::class,
            \Database\Seeders\Generated\ExpenseCategoriesSeeder::class,
            \Database\Seeders\Generated\EyeServicesSeeder::class,
            \Database\Seeders\Generated\EyeTestParametersSeeder::class,
            \Database\Seeders\Generated\EyeTestTemplatesSeeder::class,
            \Database\Seeders\Generated\FacilityUsersSeeder::class,
            \Database\Seeders\Generated\FailedJobsSeeder::class,
            \Database\Seeders\Generated\IdPrefixSettingsSeeder::class,
            \Database\Seeders\Generated\ImagingModalitiesSeeder::class,
            \Database\Seeders\Generated\InsuranceProvidersSeeder::class,
            \Database\Seeders\Generated\JitsiSettingsSeeder::class,
            \Database\Seeders\Generated\JobsSeeder::class,
            \Database\Seeders\Generated\LabConsumablesSeeder::class,
            \Database\Seeders\Generated\LabCriticalValuesSeeder::class,
            \Database\Seeders\Generated\LabEquipmentSeeder::class,
            \Database\Seeders\Generated\LabReagentsSeeder::class,
            \Database\Seeders\Generated\LabReferenceRangesSeeder::class,
            \Database\Seeders\Generated\LabRequestTemplatesSeeder::class,
            \Database\Seeders\Generated\LabTestCategoriesSeeder::class,
            \Database\Seeders\Generated\LabTestTemplatesSeeder::class,
            \Database\Seeders\Generated\LabTestsSeeder::class,
            \Database\Seeders\Generated\MobileAppSettingsSeeder::class,
            \Database\Seeders\Generated\ModelHasPermissionsSeeder::class,
            \Database\Seeders\Generated\ModelHasRolesSeeder::class,
            \Database\Seeders\Generated\NotificationsSeeder::class,
            \Database\Seeders\Generated\OrderItemsSeeder::class,
            \Database\Seeders\Generated\PasswordResetTokensSeeder::class,
            \Database\Seeders\Generated\PaymentSettingsSeeder::class,
            \Database\Seeders\Generated\PermissionsSeeder::class,
            \Database\Seeders\Generated\PersonalAccessTokensSeeder::class,
            \Database\Seeders\Generated\RadiologyDepartmentsSeeder::class,
            \Database\Seeders\Generated\RadiologyEquipmentSeeder::class,
            \Database\Seeders\Generated\RadiologyProtocolsSeeder::class,
            \Database\Seeders\Generated\RadiologyReportsSeeder::class,
            \Database\Seeders\Generated\RadiologyStudiesSeeder::class,
            \Database\Seeders\Generated\RoleHasPermissionsSeeder::class,
            \Database\Seeders\Generated\RolesSeeder::class,
            \Database\Seeders\Generated\ServicePricingSeeder::class,
            \Database\Seeders\Generated\SessionsSeeder::class,
            \Database\Seeders\Generated\SettingsSeeder::class,
            \Database\Seeders\Generated\SmsSettingsSeeder::class,
            \Database\Seeders\Generated\StaffProfilesSeeder::class,
            \Database\Seeders\Generated\StoreItemsSeeder::class,
            \Database\Seeders\Generated\StoreOrdersSeeder::class,
            \Database\Seeders\Generated\SuppliersSeeder::class,
            \Database\Seeders\Generated\SyncSettingsSeeder::class,
            \Database\Seeders\Generated\SystemSettingsSeeder::class,
            \Database\Seeders\Generated\TemplateAssignmentsSeeder::class,
            \Database\Seeders\Generated\UserNotificationPreferencesSeeder::class,
            \Database\Seeders\Generated\UsersSeeder::class,
            \Database\Seeders\Generated\WorkflowActionLogsSeeder::class,
            \Database\Seeders\Generated\WorkflowInstancesSeeder::class,
            \Database\Seeders\Generated\WorkflowStepsSeeder::class,
            \Database\Seeders\Generated\WorkflowTransitionsSeeder::class,
            \Database\Seeders\Generated\WorkflowsSeeder::class,
            \Database\Seeders\Generated\BranchSettingsSeeder::class,
            \Database\Seeders\Generated\EmergencyVisitsSeeder::class,
            \Database\Seeders\Generated\LabTestParametersSeeder::class,
            \Database\Seeders\Generated\LabTestTypesSeeder::class,
            \Database\Seeders\Generated\EmergencyAlertsSeeder::class,
        ]);
    }
}
