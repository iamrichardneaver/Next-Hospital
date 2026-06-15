<?php

namespace App\Support;

class PermissionModuleGuesser
{
    /**
     * Guess the module grouping for a permission name.
     */
    public static function guess(string $name): string
    {
        if (str_starts_with($name, 'teleconsultation.')) {
            return 'teleconsultation';
        }

        $actions = [
            'acknowledge_', 'approve_', 'calculate_', 'call_', 'cancel_', 'complete_', 'create_',
            'delete_', 'dispense_', 'download_', 'edit_', 'enter_', 'export_', 'generate_',
            'manage_', 'mark_', 'perform_', 'print_', 'process_', 'receive_', 'record_', 'resolve_',
            'search_', 'send_', 'serve_', 'sign_', 'start_', 'triage_', 'upload_', 'verify_', 'view_',
            'amend_',
        ];

        $rest = $name;
        foreach ($actions as $action) {
            if (str_starts_with($name, $action)) {
                $rest = substr($name, strlen($action));
                break;
            }
        }

        $moduleMap = [
            'dashboard' => 'dashboard',
            'audit_logs' => 'system',
            'backups' => 'system',
            'settings' => 'system',
            'system_settings' => 'system',
            'workflow_dashboard' => 'workflow',
            'workflow_settings' => 'workflow',
            'queue_statistics' => 'queues',
            'queue_priorities' => 'queues',
            'data_cleanup' => 'system',
            'files' => 'system',
            'notifications' => 'communications',
            'notification_read' => 'communications',
            'messages' => 'communications',
            'own_appointments' => 'patient_portal',
            'own_expenses' => 'expenses',
            'own_invoices' => 'patient_portal',
            'own_lab_requests' => 'patient_portal',
            'own_prescriptions' => 'patient_portal',
            'pending_registrations' => 'registrations',
            'patient_registrations' => 'registrations',
            'walk_ins_register' => 'walk_ins',
            'walk_ins' => 'walk_ins',
            'drug_formulary' => 'pharmacy',
            'pharmacy_' => 'pharmacy',
            'lab_' => 'laboratory',
            'radiology_' => 'radiology',
            'dicom_viewer' => 'radiology',
            'imaging_modalities' => 'radiology',
            'insurance_' => 'insurance',
            'insurance' => 'insurance',
            'pre_authorizations' => 'insurance',
            'coverage_policies' => 'insurance',
            'debtor' => 'billing',
            'debtors' => 'billing',
            'invoices' => 'billing',
            'payments' => 'billing',
            'billing' => 'billing',
            'expenses' => 'expenses',
            'balance_sheet' => 'accounting',
            'cash_flow' => 'accounting',
            'financial_' => 'accounting',
            'revenue_' => 'accounting',
            'cashier_reports' => 'accounting',
            'payment_methods' => 'billing',
            'service_pricing' => 'pricing',
            'pricing_rules' => 'pricing',
            'store_' => 'ecommerce',
            'ecommerce' => 'ecommerce',
            'delivery_riders' => 'ecommerce',
            'deliveries' => 'ecommerce',
            'emergency_' => 'emergency',
            'emergency' => 'emergency',
            'opd_queue' => 'queues',
            'triage_queue' => 'queues',
            'queues' => 'queues',
            'surgery' => 'surgery',
            'wards' => 'wards',
            'beds' => 'wards',
            'vitals' => 'vitals',
            'complaints' => 'complaints',
            'users' => 'users',
            'roles' => 'users',
            'permissions' => 'users',
            'branches' => 'branches',
            'reports' => 'reports',
            'patients' => 'patients',
            'appointments' => 'appointments',
            'appointment_' => 'appointments',
            'doctor_schedules' => 'appointments',
            'consultations' => 'consultations',
            'prescriptions' => 'prescriptions',
            'drugs' => 'pharmacy',
            'inventory' => 'inventory',
            'visits' => 'visits',
        ];

        foreach ($moduleMap as $needle => $module) {
            if ($rest === $needle || str_starts_with($rest, $needle)) {
                return $module;
            }
        }

        if (str_contains($rest, '_')) {
            $parts = explode('_', $rest);

            return $parts[0];
        }

        return $rest ?: 'discovered';
    }
}
