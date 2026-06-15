<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdPrefixSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'company_prefix',
        'module_prefix',
        'pattern',
        'sequence_length',
        'current_sequence',
        'include_year',
        'include_month',
        'include_day',
        'separator',
        'is_locked',
        'is_active',
        'description'
    ];

    protected $casts = [
        'include_year' => 'boolean',
        'include_month' => 'boolean',
        'include_day' => 'boolean',
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Generate next ID for entity type
     */
    public static function generateId($entityType)
    {
        return \DB::transaction(function () use ($entityType) {
            $setting = static::where('entity_type', $entityType)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$setting) {
                throw new \Exception("ID prefix setting not found for entity type: {$entityType}");
            }

            [$table, $idField] = $setting->resolveStorageTarget();

            do {
                $setting->increment('current_sequence');
                $setting->refresh();
                $id = $setting->formatId();
            } while ($table && \DB::table($table)->where($idField, $id)->exists());

            return $id;
        });
    }

    /**
     * Format ID according to setting
     */
    public function formatId()
    {
        $year = $this->include_year ? date('Y') : '';
        $month = $this->include_month ? date('m') : '';
        $day = $this->include_day ? date('d') : '';
        $sequence = str_pad($this->current_sequence, $this->sequence_length, '0', STR_PAD_LEFT);

        $id = str_replace([
            '{company_prefix}',
            '{module_prefix}',
            '{year}',
            '{month}',
            '{day}',
            '{sequence}'
        ], [
            $this->company_prefix,
            $this->module_prefix,
            $year,
            $month,
            $day,
            $sequence
        ], $this->pattern);

        return $id;
    }

    /**
     * Check if setting can be modified (not locked)
     */
    public function canBeModified()
    {
        return !$this->is_locked;
    }

    /**
     * Lock the setting (called when records exist)
     */
    public function lock()
    {
        $this->update(['is_locked' => true]);
    }

    /**
     * Check if records exist for this entity type
     */
    public function hasRecords()
    {
        $tableName = $this->getTableNameForEntity();
        
        if (!$tableName) {
            return false;
        }

        try {
            return \DB::table($tableName)->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get table name for entity type
     */
    private function getTableNameForEntity()
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
            'pharmacy_purchase_order' => 'pharmacy_purchase_orders',
            'lab_purchase_order' => 'lab_purchase_orders',
            'radiology_purchase_order' => 'radiology_purchase_orders',
        ];

        return $entityTableMap[$this->entity_type] ?? null;
    }

    /**
     * @return array{0: ?string, 1: string}
     */
    public function resolveStorageTarget(): array
    {
        $table = $this->getTableNameForEntity();
        $idField = match ($this->entity_type) {
            'pharmacy_purchase_order', 'lab_purchase_order', 'radiology_purchase_order' => 'po_number',
            'expense' => 'expense_reference',
            'supplier' => 'supplier_code',
            'branch' => 'branch_number',
            'patient' => 'patient_number',
            'visit' => 'visit_token',
            default => 'id',
        };

        return [$table, $idField];
    }

    /**
     * Auto-lock if records exist
     */
    public function autoLockIfNeeded()
    {
        if (!$this->is_locked && $this->hasRecords()) {
            $this->lock();
        }
    }

    /**
     * Get all active entity types
     */
    public static function getActiveEntityTypes()
    {
        return static::where('is_active', true)
            ->pluck('entity_type')
            ->toArray();
    }

    /**
     * Reset sequence for entity type
     */
    public static function resetSequence($entityType)
    {
        return static::where('entity_type', $entityType)
            ->update(['current_sequence' => 0]);
    }
}
