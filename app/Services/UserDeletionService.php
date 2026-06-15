<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class UserDeletionService
{
    /**
     * Tables where rows referencing the user should be deleted (operational / audit).
     */
    private const DELETE_ROW_TABLES = [
        'settings_audit_log' => ['user_id'],
        'lab_inventory_movements' => ['performed_by'],
        'radiology_inventory_movements' => ['performed_by'],
        'staff_attendances' => ['user_id', 'staff_id'],
        'login_audit' => ['user_id'],
        'activity_logs' => ['user_id', 'causer_id'],
        'notifications' => ['recipient_id', 'user_id'],
        'user_notification_preferences' => ['user_id'],
        'facility_users' => ['user_id'],
        'conversation_participants' => ['user_id'],
        'personal_access_tokens' => ['tokenable_id'],
        'sessions' => ['user_id'],
        'devices' => ['user_id'],
        'radiology_technicians' => ['user_id'],
        'delivery_riders' => ['user_id'],
    ];

    /**
     * Protected catalog tables: reassign non-nullable user references to the acting admin.
     */
    private const REASSIGN_ROW_TABLES = [
        'service_pricing' => ['created_by', 'updated_by'],
        'pricing_rules' => ['created_by', 'updated_by'],
        'discount_schemes' => ['created_by'],
        'eye_services' => ['created_by', 'updated_by'],
        'insurance_coverage' => ['created_by'],
        'insurance_coverage_policies' => ['created_by'],
        'appointment_fees' => ['created_by', 'updated_by', 'doctor_id'],
        'appointment_slots' => ['created_by', 'updated_by', 'doctor_id'],
        'doctor_schedules' => ['created_by', 'updated_by', 'doctor_id'],
        'lab_test_templates' => ['created_by', 'updated_by'],
    ];

    /**
     * Safeguard-only block reasons (does not block on clinical/financial history).
     */
    public function getBlockReason(User $user, User $actor): ?string
    {
        if ($user->id === $actor->id) {
            return 'You cannot delete your own account.';
        }

        if ($user->hasRole('super_admin') && !$actor->hasRole('super_admin')) {
            return 'Only a super admin can delete super admin accounts.';
        }

        if ($user->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
            return 'Cannot delete the last super admin account.';
        }

        return null;
    }

    /**
     * Delete a user and cascade linked patient/staff records.
     *
     * @return array{deleted_patient: bool, patient_number: ?string, deleted_staff_profile: bool}
     */
    public function deleteUser(User $user, User $actor): array
    {
        $blockReason = $this->getBlockReason($user, $actor);
        if ($blockReason) {
            throw new \RuntimeException($blockReason);
        }

        $user->loadMissing(['staffProfile', 'patient', 'roles']);

        $summary = [
            'deleted_patient' => false,
            'patient_number' => null,
            'deleted_staff_profile' => false,
        ];

        if ($user->patient) {
            $summary['patient_number'] = $user->patient->patient_number;
            $this->cascadeDeletePatient($user->patient);
            $summary['deleted_patient'] = true;
            $user->unsetRelation('patient');
        }

        $this->detachUserReferences((int) $user->id, (int) $actor->id);
        $this->cleanupUserOwnedRecords($user);

        if ($user->staffProfile) {
            $user->staffProfile->delete();
            $summary['deleted_staff_profile'] = true;
        }

        $user->syncRoles([]);
        $user->syncPermissions([]);
        $user->delete();

        return $summary;
    }

    public function buildSuccessMessage(User $user, array $summary): string
    {
        $message = "Deleted user {$user->email}";

        if ($summary['deleted_patient']) {
            $suffix = $summary['patient_number']
                ? " ({$summary['patient_number']})"
                : '';
            $message .= " and linked patient record{$suffix}";
        } elseif ($summary['deleted_staff_profile']) {
            $message .= ' and staff profile';
        }

        return $message . '.';
    }

    /**
     * Parse FK violation into a user-friendly block message when deletion is impossible.
     */
    public function describeDeletionFailure(\Throwable $e): string
    {
        $message = $e->getMessage();

        if (preg_match('/`([^`]+)`\.`([^`]+)`/', $message, $matches)) {
            $table = str_replace('_', ' ', $matches[1]);
            $column = str_replace('_', ' ', $matches[2]);

            return "Cannot delete user: linked {$table} records ({$column}) must be reassigned or removed first.";
        }

        if ($e instanceof \RuntimeException) {
            return $e->getMessage();
        }

        return 'Failed to delete user. The account may still be referenced by system records.';
    }

    private function cascadeDeletePatient(Patient $patient): void
    {
        $patientId = (int) $patient->id;
        $protected = config('data_cleanup.protected_tables', []);
        $deletionOrder = config('data_cleanup.deletion_order', []);
        $context = $this->buildPatientDeletionContext($patientId);

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($deletionOrder as $table) {
                if (in_array($table, $protected, true) || $table === 'patients') {
                    continue;
                }

                if (!Schema::hasTable($table)) {
                    continue;
                }

                $this->deletePatientScopedRows($table, $patientId, $context);
            }

            DB::table('patients')->where('id', $patientId)->delete();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function buildPatientDeletionContext(int $patientId): array
    {
        $visitIds = $this->pluckIds('visits', 'patient_id', $patientId);
        $consultationIds = $this->pluckIds('consultations', 'patient_id', $patientId);
        $appointmentIds = $this->pluckIds('appointments', 'patient_id', $patientId);
        $invoiceIds = $this->pluckIds('invoices', 'patient_id', $patientId);
        $prescriptionIds = $this->pluckIds('prescriptions', 'patient_id', $patientId);
        $labRequestIds = $this->pluckIds('lab_requests', 'patient_id', $patientId);
        $radiologyRequestIds = $this->pluckIds('radiology_requests', 'patient_id', $patientId);
        $radiologyStudyIds = $this->pluckIds('radiology_studies', 'patient_id', $patientId);
        $radiologySeriesIds = $this->pluckIdsWhereIn('radiology_series', 'study_id', $radiologyStudyIds);
        $emergencyVisitIds = $this->pluckIds('emergency_visits', 'patient_id', $patientId);
        $teleconsultationIds = $this->pluckIds('teleconsultations', 'patient_id', $patientId);
        $storeOrderIds = $this->pluckIds('store_orders', 'patient_id', $patientId);
        $insurancePolicyIds = $this->pluckIds('insurance_policies', 'patient_id', $patientId);
        $debtorIds = $this->pluckIds('debtors', 'patient_id', $patientId);
        $eyeTestRequestIds = $this->pluckIds('eye_test_requests', 'patient_id', $patientId);
        $surgeryScheduleIds = $this->pluckIds('surgery_schedules', 'patient_id', $patientId);
        $workflowInstanceIds = $this->pluckIds('workflow_instances', 'patient_id', $patientId);
        $scanIds = $this->pluckIds('scans', 'patient_id', $patientId);
        $queueIds = $this->pluckIds('queues', 'patient_id', $patientId);

        return [
            'visit_ids' => $visitIds,
            'consultation_ids' => $consultationIds,
            'appointment_ids' => $appointmentIds,
            'invoice_ids' => $invoiceIds,
            'prescription_ids' => $prescriptionIds,
            'lab_request_ids' => $labRequestIds,
            'radiology_request_ids' => $radiologyRequestIds,
            'radiology_study_ids' => $radiologyStudyIds,
            'radiology_series_ids' => $radiologySeriesIds,
            'emergency_visit_ids' => $emergencyVisitIds,
            'teleconsultation_ids' => $teleconsultationIds,
            'store_order_ids' => $storeOrderIds,
            'insurance_policy_ids' => $insurancePolicyIds,
            'debtor_ids' => $debtorIds,
            'eye_test_request_ids' => $eyeTestRequestIds,
            'surgery_schedule_ids' => $surgeryScheduleIds,
            'workflow_instance_ids' => $workflowInstanceIds,
            'scan_ids' => $scanIds,
            'queue_ids' => $queueIds,
        ];
    }

    private function deletePatientScopedRows(string $table, int $patientId, array $context): void
    {
        if (Schema::hasColumn($table, 'patient_id')) {
            DB::table($table)->where('patient_id', $patientId)->delete();

            return;
        }

        $columnMap = [
            'visit_id' => 'visit_ids',
            'consultation_id' => 'consultation_ids',
            'appointment_id' => 'appointment_ids',
            'invoice_id' => 'invoice_ids',
            'prescription_id' => 'prescription_ids',
            'lab_request_id' => 'lab_request_ids',
            'radiology_request_id' => 'radiology_request_ids',
            'study_id' => 'radiology_study_ids',
            'series_id' => 'radiology_series_ids',
            'emergency_visit_id' => 'emergency_visit_ids',
            'teleconsultation_id' => 'teleconsultation_ids',
            'store_order_id' => 'store_order_ids',
            'insurance_policy_id' => 'insurance_policy_ids',
            'debtor_id' => 'debtor_ids',
            'eye_test_request_id' => 'eye_test_request_ids',
            'surgery_schedule_id' => 'surgery_schedule_ids',
            'workflow_instance_id' => 'workflow_instance_ids',
            'scan_id' => 'scan_ids',
            'queue_id' => 'queue_ids',
        ];

        foreach ($columnMap as $column => $contextKey) {
            if (!Schema::hasColumn($table, $column)) {
                continue;
            }

            /** @var Collection<int, int|string> $ids */
            $ids = $context[$contextKey] ?? collect();
            if ($ids->isNotEmpty()) {
                DB::table($table)->whereIn($column, $ids->all())->delete();
            }

            return;
        }
    }

    private function detachUserReferences(int $userId, int $fallbackUserId): void
    {
        foreach ($this->getUserForeignKeyReferences() as $ref) {
            $this->resolveUserReference(
                $ref['table'],
                $ref['column'],
                $userId,
                $fallbackUserId,
                $ref['nullable']
            );
        }

        foreach (self::DELETE_ROW_TABLES as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }

                if ($table === 'personal_access_tokens') {
                    DB::table($table)
                        ->where($column, $userId)
                        ->where('tokenable_type', User::class)
                        ->delete();
                    continue;
                }

                if ($table === 'activity_logs' && $column === 'causer_id') {
                    DB::table($table)
                        ->where($column, $userId)
                        ->where('causer_type', User::class)
                        ->delete();
                    continue;
                }

                DB::table($table)->where($column, $userId)->delete();
            }
        }

        foreach (self::REASSIGN_ROW_TABLES as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::table($table)->where($column, $userId)->update([$column => $fallbackUserId]);
            }
        }

        $this->nullifyKnownUserColumns($userId);
        $this->reassignKnownUserColumns($userId, $fallbackUserId);
    }

    private function resolveUserReference(
        string $table,
        string $column,
        int $userId,
        int $fallbackUserId,
        bool $nullable
    ): void {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        if (in_array($table, config('data_cleanup.protected_tables', []), true)) {
            if (isset(self::DELETE_ROW_TABLES[$table])) {
                DB::table($table)->where($column, $userId)->delete();

                return;
            }

            if ($nullable) {
                DB::table($table)->where($column, $userId)->update([$column => null]);
            } else {
                DB::table($table)->where($column, $userId)->update([$column => $fallbackUserId]);
            }

            return;
        }

        if ($nullable) {
            DB::table($table)->where($column, $userId)->update([$column => null]);

            return;
        }

        if ($this->shouldDeleteRowsForTable($table, $column)) {
            DB::table($table)->where($column, $userId)->delete();

            return;
        }

        DB::table($table)->where($column, $userId)->update([$column => $fallbackUserId]);
    }

    private function shouldDeleteRowsForTable(string $table, string $column): bool
    {
        if (isset(self::DELETE_ROW_TABLES[$table]) && in_array($column, self::DELETE_ROW_TABLES[$table], true)) {
            return true;
        }

        $operationalTables = collect(config('data_cleanup.modules', []))
            ->pluck('tables')
            ->flatten()
            ->unique()
            ->all();

        return in_array($table, $operationalTables, true);
    }

    private function nullifyKnownUserColumns(int $userId): void
    {
        $nullableColumns = [
            'patients' => ['user_id', 'created_by', 'updated_by', 'activated_by'],
            'visits' => ['assigned_doctor_id', 'assigned_nurse_id'],
            'consultations' => ['created_by', 'updated_by', 'called_by'],
            'appointments' => ['created_by', 'updated_by'],
            'lab_requests' => ['created_by', 'updated_by'],
            'prescriptions' => ['created_by'],
            'invoices' => ['created_by', 'updated_by'],
            'payments' => ['processed_by', 'created_by', 'updated_by'],
            'branches' => ['created_by', 'updated_by'],
            'emergency_visits' => ['assigned_doctor_id', 'assigned_nurse_id'],
        ];

        foreach ($nullableColumns as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::table($table)->where($column, $userId)->update([$column => null]);
            }
        }
    }

    private function reassignKnownUserColumns(int $userId, int $fallbackUserId): void
    {
        $requiredColumns = [
            'consultations' => ['doctor_id'],
            'appointments' => ['doctor_id'],
            'lab_requests' => ['doctor_id'],
            'prescriptions' => ['doctor_id'],
            'surgery_schedules' => ['surgeon_id'],
            'radiology_requests' => ['doctor_id'],
            'radiology_reports' => ['radiologist_id'],
            'pre_authorizations' => ['requested_by'],
            'eye_test_requests' => ['requested_by', 'created_by'],
            'eye_test_comments' => ['commented_by'],
            'eye_test_images' => ['uploaded_by'],
            'lab_result_comments' => ['commented_by'],
            'study_contrast_usage' => ['administered_by'],
        ];

        foreach ($requiredColumns as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::table($table)->where($column, $userId)->update([$column => $fallbackUserId]);
            }
        }
    }

    private function cleanupUserOwnedRecords(User $user): void
    {
        if (Schema::hasTable('patients')) {
            DB::table('patients')->where('user_id', $user->id)->update(['user_id' => null]);
        }

        $user->forgetCachedPermissions();
    }

    /**
     * @return array<int, array{table: string, column: string, nullable: bool}>
     */
    private function getUserForeignKeyReferences(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        try {
            $rows = DB::select("
                SELECT kcu.TABLE_NAME AS table_name, kcu.COLUMN_NAME AS column_name, c.IS_NULLABLE AS is_nullable
                FROM information_schema.KEY_COLUMN_USAGE kcu
                JOIN information_schema.COLUMNS c
                  ON c.TABLE_SCHEMA = kcu.TABLE_SCHEMA
                 AND c.TABLE_NAME = kcu.TABLE_NAME
                 AND c.COLUMN_NAME = kcu.COLUMN_NAME
                WHERE kcu.REFERENCED_TABLE_SCHEMA = DATABASE()
                  AND kcu.REFERENCED_TABLE_NAME = 'users'
                  AND kcu.REFERENCED_COLUMN_NAME = 'id'
                ORDER BY kcu.TABLE_NAME, kcu.COLUMN_NAME
            ");
        } catch (\Throwable $e) {
            Log::warning('Could not load user FK references for deletion', ['error' => $e->getMessage()]);

            return $cache = [];
        }

        $cache = array_map(static function ($row) {
            return [
                'table' => $row->table_name,
                'column' => $row->column_name,
                'nullable' => ($row->is_nullable ?? 'NO') === 'YES',
            ];
        }, $rows);

        return $cache;
    }

    private function pluckIds(string $table, string $column, int $value): Collection
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return collect();
        }

        return DB::table($table)->where($column, $value)->pluck('id');
    }

    private function pluckIdsWhereIn(string $table, string $column, Collection $parentIds): Collection
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column) || $parentIds->isEmpty()) {
            return collect();
        }

        return DB::table($table)->whereIn($column, $parentIds->all())->pluck('id');
    }
}
