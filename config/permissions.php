<?php

/**
 * Canonical web-guard permission registry.
 *
 * HOW TO ADD A NEW PERMISSION
 * 1. Use the permission in code (route middleware, @can, policies, seeders, etc.).
 * 2. Optionally add a description/module entry below for UI grouping.
 * 3. Permissions auto-sync on boot when the codebase changes; or run: php artisan permissions:sync
 * 4. Assign to roles via the admin UI (/permissions, /roles) or Refine* seeders.
 *
 * Deploy: `php artisan permissions:sync` is optional when auto_sync_on_boot is enabled.
 * Safe to re-run — uses firstOrCreate; never removes existing assignments.
 *
 * @see App\Services\PermissionSyncService
 * @see App\Support\PermissionScanner
 * @see Database\Seeders\DatabaseSeeder Permission seeding documentation
 */

return [

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Auto-sync on application boot
    |--------------------------------------------------------------------------
    |
    | When enabled, new permissions referenced in code are upserted automatically
    | when source files change (fingerprint cached — not on every request).
    | Disable in tests via PERMISSIONS_AUTO_SYNC=false.
    |
    */
    'auto_sync_on_boot' => env('PERMISSIONS_AUTO_SYNC', true),

    /*
    |--------------------------------------------------------------------------
    | Codebase scan paths
    |--------------------------------------------------------------------------
    |
    | Directories scanned for permission strings (routes, @can, middleware, seeders).
    |
    */
    'scan_paths' => [
        base_path('routes'),
        base_path('app'),
        base_path('resources/views'),
        base_path('database/seeders'),
    ],

    'modules' => [
        'accounting' => [
            'export_financial_reports' => null,
            'export_revenue_analytics' => null,
            'export_revenue_reports' => null,
            'view_balance_sheet' => null,
            'view_cash_flow' => null,
            'view_cashier_reports' => null,
            'view_financial_analytics' => null,
            'view_financial_dashboard' => null,
            'view_financial_reports' => null,
            'view_revenue_analytics' => null,
            'view_revenue_reports' => null,
        ],

        'alerts' => [
            'acknowledge_alerts' => null,
            'resolve_alerts' => null,
        ],

        'appointments' => [
            'create_appointment_fees' => null,
            'create_appointment_slots' => null,
            'create_appointments' => null,
            'create_doctor_reviews' => null,
            'create_doctor_schedules' => null,
            'delete_appointment_fees' => null,
            'delete_appointment_slots' => null,
            'delete_appointments' => null,
            'delete_doctor_schedules' => null,
            'edit_appointment_fees' => null,
            'edit_appointment_slots' => null,
            'edit_appointments' => null,
            'edit_doctor_schedules' => null,
            'manage_appointments' => null,
            'view_appointment_fees' => null,
            'view_appointment_slots' => null,
            'view_appointments' => null,
            'view_doctor_reviews' => null,
            'view_doctor_schedules' => null,
        ],

        'billing' => [
            'create_debtors' => null,
            'create_invoices' => null,
            'create_payments' => null,
            'delete_debtors' => null,
            'delete_invoices' => null,
            'delete_payments' => null,
            'edit_debtors' => null,
            'edit_invoices' => null,
            'edit_payments' => null,
            'manage_billing' => null,
            'manage_debtor_payments' => null,
            'manage_debtors' => null,
            'process_payments' => null,
            'view_debtors' => null,
            'view_invoices' => null,
            'view_payment_methods' => null,
            'view_payments' => null,
        ],

        'branch' => [
            'view_branch_revenue' => null,
        ],

        'branches' => [
            'create_branches' => null,
            'delete_branches' => null,
            'edit_branches' => null,
            'manage_branches' => null,
            'view_branches' => null,
        ],

        'communications' => [
            'create_messages' => null,
            'create_notifications' => null,
            'delete_messages' => null,
            'delete_notifications' => null,
            'edit_notifications' => null,
            'mark_notification_read' => null,
            'send_messages' => null,
            'view_messages' => null,
            'view_notifications' => null,
        ],

        'complaints' => [
            'create_complaints' => null,
            'delete_complaints' => null,
            'edit_complaints' => null,
            'manage_complaints' => null,
            'resolve_complaints' => null,
            'view_complaints' => null,
        ],

        'consultations' => [
            'create_consultations' => null,
            'delete_consultations' => null,
            'edit_consultations' => null,
            'manage_consultations' => null,
            'view_consultations' => null,
        ],

        'dashboard' => [
            'view_dashboard' => null,
        ],

        'data' => [
            'export_data' => null,
        ],

        'department' => [
            'view_department_revenue' => null,
        ],

        'ecommerce' => [
            'cancel_store_orders' => null,
            'create_delivery_riders' => null,
            'create_store_items' => null,
            'create_store_orders' => null,
            'delete_delivery_riders' => null,
            'delete_store_items' => null,
            'delete_store_orders' => null,
            'edit_delivery_riders' => null,
            'edit_store_items' => null,
            'edit_store_orders' => null,
            'manage_deliveries' => null,
            'manage_delivery_riders' => null,
            'manage_store_items' => null,
            'manage_store_orders' => null,
            'process_store_orders' => null,
            'view_deliveries' => null,
            'view_delivery_riders' => null,
            'view_ecommerce' => null,
            'view_store_items' => null,
            'view_store_orders' => null,
        ],

        'emergency' => [
            'acknowledge_emergency_alerts' => null,
            'create_emergency' => null,
            'create_emergency_alerts' => null,
            'create_emergency_visits' => null,
            'delete_emergency' => null,
            'delete_emergency_alerts' => null,
            'delete_emergency_visits' => null,
            'edit_emergency' => null,
            'edit_emergency_alerts' => null,
            'edit_emergency_visits' => null,
            'manage_emergency' => null,
            'manage_emergency_queue' => null,
            'resolve_emergency_alerts' => null,
            'view_emergency' => null,
            'view_emergency_alerts' => null,
            'view_emergency_queue' => null,
            'view_emergency_visits' => null,
        ],

        'expenses' => [
            'approve_expenses' => 'Approve or reject pending department expenses',
            'create_expenses' => 'Submit operational expenses for department approval',
            'manage_expenses' => 'Full expense module management (accounting)',
            'view_expenses' => 'View all department expenses',
            'view_own_expenses' => 'View own submitted expense records',
        ],

        'insurance' => [
            'calculate_insurance_coverage' => null,
            'create_coverage_policies' => null,
            'create_insurance' => null,
            'create_insurance_claims' => null,
            'create_insurance_policies' => null,
            'create_insurance_providers' => null,
            'create_pre_authorizations' => null,
            'delete_coverage_policies' => null,
            'delete_insurance' => null,
            'delete_insurance_claims' => null,
            'delete_insurance_policies' => null,
            'delete_insurance_providers' => null,
            'delete_pre_authorizations' => null,
            'edit_coverage_policies' => null,
            'edit_insurance' => null,
            'edit_insurance_claims' => null,
            'edit_insurance_policies' => null,
            'edit_insurance_providers' => null,
            'edit_pre_authorizations' => null,
            'manage_insurance_claims' => null,
            'manage_insurance_policies' => null,
            'manage_insurance_providers' => null,
            'manage_insurance_reports' => null,
            'process_insurance_claims' => null,
            'view_coverage_policies' => null,
            'view_insurance' => null,
            'view_insurance_analytics' => null,
            'view_insurance_claims' => null,
            'view_insurance_policies' => null,
            'view_insurance_providers' => null,
            'view_pre_authorizations' => null,
        ],

        'inventory' => [
            'create_inventory' => null,
            'delete_inventory' => null,
            'edit_inventory' => null,
            'manage_inventory' => null,
            'manage_inventory_stock' => null,
            'view_inventory' => null,
        ],

        'laboratory' => [
            'approve_lab_results' => null,
            'create_lab_purchases' => null,
            'create_lab_requests' => null,
            'delete_lab_requests' => null,
            'edit_lab_requests' => null,
            'enter_lab_results' => null,
            'generate_lab_reports' => null,
            'manage_lab_categories' => null,
            'manage_lab_parameters' => null,
            'manage_lab_queue' => null,
            'manage_lab_reference_ranges' => null,
            'manage_lab_results' => null,
            'manage_lab_setup' => null,
            'manage_lab_suppliers' => null,
            'manage_lab_templates' => null,
            'manage_lab_test_consumables' => null,
            'manage_lab_tests' => null,
            'perform_lab_tests' => null,
            'print_lab_results' => null,
            'receive_lab_purchases' => null,
            'verify_lab_results' => null,
            'view_lab_inventory' => null,
            'view_lab_purchases' => null,
            'view_lab_queue' => null,
            'view_lab_requests' => null,
            'view_lab_results' => null,
            'view_lab_suppliers' => null,
        ],

        'patient_portal' => [
            'view_own_appointments' => null,
            'view_own_invoices' => null,
            'view_own_lab_requests' => null,
            'view_own_prescriptions' => null,
        ],

        'patients' => [
            'call_patients' => null,
            'create_patients' => null,
            'delete_patients' => null,
            'edit_patients' => null,
            'search_patients' => null,
            'serve_patients' => null,
            'triage_patients' => null,
            'view_patients' => null,
        ],

        'pharmacy' => [
            'create_drugs' => null,
            'create_pharmacy_purchases' => null,
            'delete_drugs' => null,
            'dispense_drugs' => null,
            'edit_drugs' => null,
            'manage_pharmacy_inventory' => null,
            'manage_pharmacy_queue' => null,
            'manage_pharmacy_suppliers' => null,
            'manage_stock_counts' => null,
            'view_stock_counts' => null,
            'receive_pharmacy_purchases' => null,
            'view_drug_formulary' => null,
            'view_drugs' => null,
            'view_pharmacy_analytics' => null,
            'view_pharmacy_purchases' => null,
            'view_pharmacy_queue' => null,
            'view_pharmacy_suppliers' => null,
        ],

        'prescriptions' => [
            'create_prescriptions' => null,
            'delete_prescriptions' => null,
            'edit_prescriptions' => null,
            'view_prescriptions' => null,
        ],

        'pricing' => [
            'create_service_pricing' => null,
            'delete_service_pricing' => null,
            'edit_service_pricing' => null,
            'export_service_pricing' => null,
            'manage_pricing_rules' => null,
            'manage_service_pricing' => null,
            'view_service_pricing' => null,
        ],

        'queues' => [
            'manage_opd_queue' => null,
            'manage_queue_priorities' => null,
            'manage_queues' => null,
            'manage_triage_queue' => null,
            'view_opd_queue' => null,
            'view_queue_statistics' => null,
            'view_queues' => null,
            'view_triage_queue' => null,
        ],

        'radiology' => [
            'amend_radiology_reports' => null,
            'cancel_radiology_studies' => null,
            'complete_radiology_studies' => null,
            'create_radiology_purchases' => 'Create radiology purchase orders',
            'create_radiology_reports' => null,
            'create_radiology_requests' => null,
            'create_radiology_results' => null,
            'create_radiology_studies' => null,
            'delete_radiology_requests' => null,
            'delete_radiology_results' => null,
            'download_radiology_images' => null,
            'edit_radiology_reports' => null,
            'edit_radiology_requests' => null,
            'edit_radiology_results' => null,
            'edit_radiology_studies' => null,
            'generate_radiology_reports' => null,
            'manage_imaging_modalities' => null,
            'manage_radiology_equipment' => null,
            'manage_radiology_protocols' => null,
            'manage_radiology_results' => null,
            'manage_radiology_schedule' => null,
            'manage_radiology_setup' => null,
            'manage_radiology_suppliers' => 'Create and manage radiology suppliers',
            'manage_radiology_technicians' => null,
            'perform_radiology_qc' => null,
            'perform_radiology_studies' => null,
            'process_radiology_requests' => null,
            'receive_radiology_purchases' => 'Receive goods on radiology purchase orders',
            'sign_radiology_reports' => null,
            'start_radiology_studies' => null,
            'upload_radiology_images' => null,
            'view_dicom_viewer' => null,
            'view_radiology_inventory' => 'View radiology inventory catalog and stock levels',
            'view_radiology_purchases' => 'View radiology purchase orders',
            'view_radiology_qc' => null,
            'view_radiology_reports' => null,
            'view_radiology_requests' => null,
            'view_radiology_results' => null,
            'view_radiology_schedule' => null,
            'view_radiology_studies' => null,
            'view_radiology_suppliers' => 'View radiology suppliers',
        ],

        'registrations' => [
            'approve_patient_registrations' => null,
            'view_pending_registrations' => null,
        ],

        'reports' => [
            'export_reports' => null,
            'generate_reports' => null,
            'view_reports' => null,
        ],

        'services' => [
            'complete_services' => null,
        ],

        'surgery' => [
            'create_surgery' => null,
            'create_surgery_schedules' => null,
            'delete_surgery' => null,
            'delete_surgery_schedules' => null,
            'edit_surgery' => null,
            'edit_surgery_schedules' => null,
            'view_surgery' => null,
            'view_surgery_schedules' => null,
        ],

        'system' => [
            'manage_backups' => null,
            'manage_data_cleanup' => null,
            'manage_settings' => null,
            'manage_system_settings' => null,
            'view_audit_logs' => null,
            'view_files' => null,
            'view_settings' => null,
            'view_system_settings' => null,
        ],

        'teleconsultation' => [
            'teleconsultation.cancel' => null,
            'teleconsultation.chat.delete' => null,
            'teleconsultation.chat.edit' => null,
            'teleconsultation.chat.send' => null,
            'teleconsultation.chat.view' => null,
            'teleconsultation.consent.give' => null,
            'teleconsultation.consent.revoke' => null,
            'teleconsultation.create' => null,
            'teleconsultation.delete' => null,
            'teleconsultation.edit' => null,
            'teleconsultation.end' => null,
            'teleconsultation.files.consent' => null,
            'teleconsultation.files.delete' => null,
            'teleconsultation.files.download' => null,
            'teleconsultation.files.upload' => null,
            'teleconsultation.files.view' => null,
            'teleconsultation.recording.download' => null,
            'teleconsultation.recording.start' => null,
            'teleconsultation.recording.stop' => null,
            'teleconsultation.start' => null,
            'teleconsultation.statistics.view' => null,
            'teleconsultation.view' => null,
        ],

        'users' => [
            'create_users' => null,
            'delete_users' => null,
            'edit_users' => null,
            'manage_permissions' => null,
            'manage_roles' => null,
            'manage_users' => null,
            'view_users' => null,
        ],

        'visits' => [
            'create_visits' => null,
            'delete_visits' => null,
            'edit_visits' => null,
            'manage_visits' => null,
            'view_visits' => null,
        ],

        'vitals' => [
            'delete_vitals' => null,
            'edit_vitals' => null,
            'view_vitals' => null,
        ],

        'walk_ins' => [
            'create_walk_ins' => null,
            'delete_walk_ins' => null,
            'edit_walk_ins' => null,
            'export_walk_ins' => null,
            'export_walk_ins_register' => null,
            'manage_walk_ins' => null,
            'view_walk_ins' => null,
            'view_walk_ins_register' => null,
        ],

        'wards' => [
            'create_beds' => null,
            'create_wards' => null,
            'delete_beds' => null,
            'delete_wards' => null,
            'edit_beds' => null,
            'edit_wards' => null,
            'manage_beds' => null,
            'manage_wards' => null,
            'view_beds' => null,
            'view_wards' => null,
        ],

        'workflow' => [
            'manage_workflow_settings' => null,
            'view_workflow_dashboard' => null,
        ],

    ],

];
