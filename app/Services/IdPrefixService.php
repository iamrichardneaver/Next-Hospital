<?php

namespace App\Services;

/**
 * ID Prefix Service
 * 
 * Generates human-readable, unique identifiers for various entities in the system.
 * Supports configurable patterns, sequences, and date formats.
 * 
 * Examples:
 * - Patient: PT-2025-00001
 * - Visit: VST-20251008-001
 * - Invoice: INV-2025-10-00045
 * - Lab Request: LAB-20251008-123
 * 
 * Features:
 * - Configurable prefix, separator, year format, and sequence length
 * - Daily or yearly sequence reset
 * - Thread-safe sequence generation using database locks
 * - Validation of patterns before ID generation
 * - Locked settings protection
 * 
 * @package App\Services
 * @author NextHospital Development Team
 * @version 1.0.0
 */

use App\Models\IdPrefixSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IdPrefixService
{
    /**
     * Generate ID for entity type
     */
    public function generateId($entityType)
    {
        try {
            return IdPrefixSetting::generateId($entityType);
        } catch (\Exception $e) {
            Log::error("Failed to generate ID for entity type: {$entityType}", [
                'error' => $e->getMessage(),
                'entity_type' => $entityType
            ]);
            throw $e;
        }
    }

    /**
     * Get or create ID prefix setting for entity type
     */
    public function getOrCreateSetting($entityType, $defaultData = [])
    {
        $setting = IdPrefixSetting::where('entity_type', $entityType)->first();

        if (!$setting) {
            $fillable = (new IdPrefixSetting())->getFillable();
            $defaults = array_merge([
                'entity_type' => $entityType,
                'company_prefix' => 'HWC',
                'module_prefix' => strtoupper(substr($entityType, 0, 3)),
                'pattern' => '{company_prefix}/{module_prefix}/{year}{month}{day}/{sequence}',
                'sequence_length' => 5,
                'current_sequence' => 0,
                'include_year' => true,
                'include_month' => true,
                'include_day' => true,
                'separator' => '/',
                'is_locked' => false,
                'is_active' => true,
                'description' => "ID pattern for {$entityType}",
            ], array_intersect_key($defaultData, array_flip($fillable)));

            $setting = IdPrefixSetting::create($defaults);
        }

        // Auto-lock if records exist
        $setting->autoLockIfNeeded();

        return $setting;
    }

    /**
     * Update ID prefix setting with security checks
     */
    public function updateSetting($entityType, $data)
    {
        $setting = IdPrefixSetting::where('entity_type', $entityType)->first();

        if (!$setting) {
            throw new \Exception("ID prefix setting not found for entity type: {$entityType}");
        }

        // Check if setting is locked
        if ($setting->is_locked) {
            throw new \Exception("Cannot modify ID prefix setting for {$entityType} because records already exist in the system. The pattern is locked for data integrity.");
        }

        // Validate that the new pattern doesn't conflict with existing IDs
        if (isset($data['pattern']) && $this->wouldConflictWithExistingIds($setting, $data['pattern'])) {
            throw new \Exception("The new pattern would conflict with existing IDs. Please choose a different pattern.");
        }

        $fillable = array_diff((new IdPrefixSetting())->getFillable(), ['entity_type']);
        $setting->update(array_intersect_key($data, array_flip($fillable)));

        return $setting->fresh();
    }

    /**
     * Check if new pattern would conflict with existing IDs
     */
    private function wouldConflictWithExistingIds($setting, $newPattern)
    {
        // Generate a test ID with the new pattern
        $testSetting = clone $setting;
        $testSetting->pattern = $newPattern;
        $testId = $testSetting->formatId();

        // Check if this ID already exists in the system
        return $this->idExistsInSystem($setting->entity_type, $testId);
    }

    /**
     * Check if ID already exists in the system
     */
    private function idExistsInSystem($entityType, $id)
    {
        $tableName = $this->getTableNameForEntity($entityType);
        
        if (!$tableName) {
            return false;
        }

        try {
            // Check if any record with this ID exists
            return DB::table($tableName)->where('id', $id)->exists();
        } catch (\Exception $e) {
            Log::error("Failed to check if ID exists", [
                'entity_type' => $entityType,
                'id' => $id,
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get table name for entity type
     */
    private function getTableNameForEntity($entityType)
    {
        $entityTableMap = [
            'patient' => 'patients',
            'staff' => 'users',
            'doctor' => 'users',
            'appointment' => 'appointments',
            'consultation' => 'consultations',
            'prescription' => 'prescriptions',
            'lab_test' => 'lab_requests',
            'lab_result' => 'lab_results',
            'invoice' => 'invoices',
            'payment' => 'payments',
            'drug' => 'drugs',
            'ward' => 'wards',
            'bed' => 'beds',
            'branch' => 'branches',
            'note' => 'notes',
            'insurance_claim' => 'insurance_claims',
            'store_item' => 'store_items',
            'bed_assignment' => 'bed_assignments',
            'vital' => 'vitals',
            'diagnosis' => 'diagnoses',
            'consultation_intervention' => 'consultation_interventions',
            'follow_up' => 'follow_ups',
            'referral' => 'referrals',
            'insurance_policy' => 'insurance_policies',
            'insurance_provider' => 'insurance_providers',
            'radiology_request' => 'radiology_requests',
            'radiology_report' => 'radiology_reports',
            'surgery_schedule' => 'surgery_schedules',
            'teleconsultation' => 'teleconsultations',
            'emergency_visit' => 'emergency_visits',
            'visit' => 'visits',
            'queue' => 'queues',
            'scan' => 'scans',
            'store_order' => 'store_orders',
            'expense' => 'expenses',
            'revenue' => 'revenue_transactions',
            'complaint' => 'complaints',
            'delivery' => 'deliveries',
            'drug_order' => 'drug_orders',
            'order_item' => 'order_items',
            'staff_profile' => 'staff_profiles',
            'supplier' => 'suppliers',
        ];

        return $entityTableMap[$entityType] ?? null;
    }

    /**
     * Get all ID prefix settings
     */
    public function getAllSettings()
    {
        return IdPrefixSetting::orderBy('entity_type')->get();
    }

    /**
     * Get setting for entity type
     */
    public function getSetting($entityType)
    {
        return IdPrefixSetting::where('entity_type', $entityType)->first();
    }

    /**
     * Test ID generation with current settings
     */
    public function testIdGeneration($entityType)
    {
        $setting = $this->getSetting($entityType);

        if (!$setting) {
            throw new \Exception("ID prefix setting not found for entity type: {$entityType}");
        }

        // Generate test ID without incrementing sequence
        $testId = $setting->formatId();

        return [
            'entity_type' => $entityType,
            'pattern' => $setting->pattern,
            'test_id' => $testId,
            'is_locked' => $setting->is_locked,
            'can_modify' => $setting->canBeModified(),
            'has_records' => $setting->hasRecords()
        ];
    }

    /**
     * Reset sequence for entity type (admin only)
     */
    public function resetSequence($entityType)
    {
        $setting = IdPrefixSetting::where('entity_type', $entityType)->first();

        if (!$setting) {
            throw new \Exception("ID prefix setting not found for entity type: {$entityType}");
        }

        if ($setting->is_locked) {
            throw new \Exception("Cannot reset sequence for locked entity type: {$entityType}");
        }

        $setting->update(['current_sequence' => 0]);

        return $setting;
    }

    /**
     * Lock setting for entity type (admin only)
     */
    public function lockSetting($entityType)
    {
        $setting = IdPrefixSetting::where('entity_type', $entityType)->first();

        if (!$setting) {
            throw new \Exception("ID prefix setting not found for entity type: {$entityType}");
        }

        $setting->lock();

        return $setting;
    }

    /**
     * Unlock setting for entity type (admin only - use with caution)
     */
    public function unlockSetting($entityType)
    {
        $setting = IdPrefixSetting::where('entity_type', $entityType)->first();

        if (!$setting) {
            throw new \Exception("ID prefix setting not found for entity type: {$entityType}");
        }

        $setting->update(['is_locked' => false]);

        return $setting;
    }

    /**
     * Get available entity types
     */
    public function getAvailableEntityTypes()
    {
        return [
            'patient' => 'Patient',
            'staff' => 'Staff',
            'doctor' => 'Doctor',
            'appointment' => 'Appointment',
            'consultation' => 'Consultation',
            'prescription' => 'Prescription',
            'lab_test' => 'Lab Test',
            'lab_result' => 'Lab Result',
            'invoice' => 'Invoice',
            'payment' => 'Payment',
            'drug' => 'Drug',
            'ward' => 'Ward',
            'bed' => 'Bed',
            'branch' => 'Branch',
            'note' => 'Note',
            'insurance_claim' => 'Insurance Claim',
            'store_item' => 'Store Item',
            'bed_assignment' => 'Bed Assignment',
            'vital' => 'Vital Sign',
            'diagnosis' => 'Diagnosis',
            'consultation_intervention' => 'Consultation Intervention',
            'follow_up' => 'Follow Up',
            'referral' => 'Referral',
            'insurance_policy' => 'Insurance Policy',
            'insurance_provider' => 'Insurance Provider',
            'radiology_request' => 'Radiology Request',
            'radiology_report' => 'Radiology Report',
            'surgery_schedule' => 'Surgery Schedule',
            'teleconsultation' => 'Teleconsultation',
            'emergency_visit' => 'Emergency Visit',
            'visit' => 'Visit',
            'queue' => 'Queue',
            'scan' => 'Scan',
            'store_order' => 'Store Order',
            'expense' => 'Expense',
            'revenue' => 'Revenue Transaction',
            'complaint' => 'Complaint',
            'delivery' => 'Delivery',
            'drug_order' => 'Drug Order',
            'order_item' => 'Order Item',
            'staff_profile' => 'Staff Profile',
            'supplier' => 'Supplier',
        ];
    }

    /**
     * Get pattern examples
     */
    public function getPatternExamples()
    {
        return [
            'HWC/PAT/25090600001' => '{company_prefix}/{module_prefix}/{year}{month}{day}{sequence}',
            'PAT-2025-09-06-00001' => '{module_prefix}-{year}-{month}-{day}-{sequence}',
            'HWC-PAT-250906-00001' => '{company_prefix}-{module_prefix}-{year}{month}{day}-{sequence}',
            'PAT25090600001' => '{module_prefix}{year}{month}{day}{sequence}',
            'HWC/PAT/2025/00001' => '{company_prefix}/{module_prefix}/{year}/{sequence}',
        ];
    }

    /**
     * Validate pattern format
     */
    public function validatePattern($pattern)
    {
        $requiredPlaceholders = ['{sequence}'];
        $optionalPlaceholders = ['{company_prefix}', '{module_prefix}', '{year}', '{month}', '{day}'];
        
        $allPlaceholders = array_merge($requiredPlaceholders, $optionalPlaceholders);
        
        // Check if pattern contains required placeholders
        foreach ($requiredPlaceholders as $placeholder) {
            if (strpos($pattern, $placeholder) === false) {
                return [
                    'valid' => false,
                    'message' => "Pattern must contain {$placeholder}"
                ];
            }
        }

        // Check for invalid placeholders
        preg_match_all('/\{[^}]+\}/', $pattern, $matches);
        foreach ($matches[0] as $placeholder) {
            if (!in_array($placeholder, $allPlaceholders)) {
                return [
                    'valid' => false,
                    'message' => "Invalid placeholder: {$placeholder}"
                ];
            }
        }

        return [
            'valid' => true,
            'message' => 'Pattern is valid'
        ];
    }
}
