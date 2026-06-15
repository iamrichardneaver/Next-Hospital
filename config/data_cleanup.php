<?php

/**
 * Data cleanup configuration.
 *
 * Tables listed in protected_tables are NEVER truncated or deleted by the
 * Settings → Data Cleanup feature, regardless of module selection.
 */
return [

    'confirmation_phrase' => 'DELETE ALL DATA',

    /*
    |--------------------------------------------------------------------------
    | Reference / configuration tables — never clean
    |--------------------------------------------------------------------------
    */
    'protected_tables' => [
        // Auth & RBAC
        'users',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',

        // System & settings
        'settings',
        'branding_settings',
        'id_prefix_settings',
        'system_settings',
        'document_settings',
        'api_settings',
        'branch_settings',
        'email_settings',
        'sms_settings',
        'payment_settings',
        'mobile_app_settings',
        'sync_settings',
        'jitsi_settings',
        'settings_audit_log',
        'app_versions',

        // Branches & staff
        'branches',
        'staff_profiles',
        'facility_users',

        // Pricing & fee structures
        'service_pricing',
        'pricing_rules',
        'discount_schemes',
        'appointment_fees',

        // Lab catalog & reference
        'lab_test_types',
        'lab_test_templates',
        'lab_test_categories',
        'lab_tests',
        'lab_test_parameters',
        'lab_reference_ranges',
        'lab_critical_values',
        'lab_delta_check_rules',
        'lab_equipment',
        'lab_reagents',
        'lab_consumables',
        'lab_quality_control',
        'lab_quality_controls',
        'lab_request_templates',

        // Pharmacy formulary
        'drugs',
        'drug_interactions',

        // Insurance seed / reference
        'insurance_providers',
        'insurance_coverage',
        'insurance_coverage_policies',
        'insurance_service_categories',

        // Accounting reference
        'expense_categories',

        // Radiology catalog
        'imaging_modalities',
        'radiology_departments',
        'radiology_equipment',
        'radiology_technicians',
        'contrast_agents',
        'radiology_protocols',
        'radiology_schedule_slots',

        // Eye services catalog
        'eye_services',
        'eye_test_templates',
        'eye_test_parameters',

        // Facility / theatre reference
        'wards',
        'beds',
        'theatres',
        'crash_carts',
        'surgery_equipment',

        // E-commerce catalog
        'store_items',
        'delivery_riders',
        'suppliers',

        // Clinical templates
        'consultation_templates',
        'template_assignments',

        // Workflow definitions
        'workflows',
        'workflow_steps',
        'workflow_transitions',

        // Laravel / infrastructure
        'migrations',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
        'sessions',
        'api_tokens',
        'devices',
        'personal_access_tokens',
    ],

    /*
    |--------------------------------------------------------------------------
    | Deletion order (children before parents) for safe FK handling
    |--------------------------------------------------------------------------
    */
    'deletion_order' => [
        // Deepest children first
        'order_items', 'claim_items', 'debtor_payment_histories', 'lab_test_results',
        'lab_result_comments', 'radiology_images', 'radiology_series', 'radiology_reports',
        'radiation_doses', 'study_contrast_usage', 'eye_test_images', 'eye_test_comments',
        'eye_test_results', 'eye_service_billing_items', 'teleconsultation_chats',
        'teleconsultation_files', 'conversation_participants', 'messages',
        'consultation_interventions', 'drug_orders', 'prescription_notifications',
        'surgery_teams', 'surgery_procedures', 'workflow_action_logs',
        'patient_allergies', 'patient_medical_history', 'patient_dependents',
        'patient_cart', 'patient_payment_methods', 'diagnoses', 'vitals', 'notes',
        'follow_ups', 'referrals', 'file_uploads', 'lab_results', 'lab_reports',
        'lab_inventory_transactions', 'lab_equipment_maintenances', 'lab_equipment_calibration',
        'scans', 'radiology_studies', 'radiology_qc_checks', 'icu_logs', 'triage_assessments',
        'emergency_interventions', 'emergency_alerts', 'bed_assignments', 'drug_stocks',
        'payments', 'revenue_transactions', 'expenses', 'pre_authorizations',
        'insurance_claims', 'insurance_policies', 'debtors', 'invoices',
        'prescriptions', 'lab_requests', 'radiology_requests', 'eye_test_requests',
        'consultations', 'appointments', 'appointment_slots', 'doctor_schedules',
        'visits', 'queues', 'emergency_visits', 'surgery_schedules', 'blood_donations',
        'blood_inventory', 'transfusions', 'store_orders', 'deliveries',
        'teleconsultations', 'conversations', 'complaints', 'notifications',
        'user_notification_preferences', 'activity_logs', 'login_audit', 'sync_logs',
        'sync_queues', 'ghs_reports', 'nhis_claims', 'staff_attendances',
        'workflow_instances', 'patients',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanable modules — operational / transient data only
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'patients' => [
            'name' => 'Patients',
            'description' => 'Patient records, demographics, allergies, and medical history (users & admins are preserved)',
            'tables' => [
                'patient_allergies',
                'patient_medical_history',
                'patient_dependents',
                'patient_cart',
                'patient_payment_methods',
                'patients',
            ],
            'icon' => 'bi-people',
            'color' => 'primary',
        ],
        'clinical' => [
            'name' => 'Clinical Records',
            'description' => 'Consultations, diagnoses, vitals, notes, and referrals (templates preserved)',
            'tables' => [
                'consultation_interventions',
                'diagnoses',
                'vitals',
                'notes',
                'follow_ups',
                'referrals',
                'file_uploads',
                'consultations',
            ],
            'icon' => 'bi-clipboard-pulse',
            'color' => 'success',
        ],
        'appointments' => [
            'name' => 'Appointments & Scheduling',
            'description' => 'Appointment bookings, slots, and doctor schedules (fee structures preserved)',
            'tables' => [
                'appointments',
                'appointment_slots',
                'doctor_schedules',
            ],
            'icon' => 'bi-calendar-event',
            'color' => 'info',
        ],
        'visits_queues' => [
            'name' => 'Visits & Queues',
            'description' => 'Patient visits and queue tickets',
            'tables' => [
                'queues',
                'visits',
            ],
            'icon' => 'bi-ticket',
            'color' => 'info',
        ],
        'laboratory' => [
            'name' => 'Laboratory (Operational)',
            'description' => 'Lab requests and results (test type catalog & templates preserved)',
            'tables' => [
                'lab_result_comments',
                'lab_test_results',
                'lab_results',
                'lab_reports',
                'lab_inventory_transactions',
                'lab_equipment_maintenances',
                'lab_equipment_calibration',
                'lab_requests',
            ],
            'icon' => 'bi-droplet',
            'color' => 'warning',
        ],
        'radiology' => [
            'name' => 'Radiology & Imaging (Operational)',
            'description' => 'Imaging requests, studies, and reports (modality catalog preserved)',
            'tables' => [
                'radiation_doses',
                'study_contrast_usage',
                'radiology_images',
                'radiology_series',
                'radiology_reports',
                'radiology_qc_checks',
                'radiology_studies',
                'scans',
                'radiology_requests',
                'eye_test_images',
                'eye_test_comments',
                'eye_test_results',
                'eye_service_billing_items',
                'eye_test_requests',
            ],
            'icon' => 'bi-camera',
            'color' => 'secondary',
        ],
        'pharmacy' => [
            'name' => 'Pharmacy (Operational)',
            'description' => 'Prescriptions, dispensing orders, and stock levels (drug formulary preserved)',
            'tables' => [
                'prescription_notifications',
                'drug_orders',
                'prescriptions',
                'drug_stocks',
            ],
            'icon' => 'bi-capsule',
            'color' => 'danger',
        ],
        'billing' => [
            'name' => 'Billing & Payments',
            'description' => 'Invoices, payments, debtors, and revenue (pricing catalog preserved)',
            'tables' => [
                'debtor_payment_histories',
                'payments',
                'revenue_transactions',
                'expenses',
                'debtors',
                'invoices',
            ],
            'icon' => 'bi-credit-card',
            'color' => 'success',
        ],
        'insurance' => [
            'name' => 'Insurance (Transactional)',
            'description' => 'Claims, policies, and pre-authorizations (provider catalog preserved)',
            'tables' => [
                'claim_items',
                'pre_authorizations',
                'insurance_claims',
                'insurance_policies',
            ],
            'icon' => 'bi-shield-check',
            'color' => 'info',
        ],
        'inpatient' => [
            'name' => 'Inpatient Care',
            'description' => 'Bed assignments and ICU logs (ward/bed catalog preserved)',
            'tables' => [
                'icu_logs',
                'bed_assignments',
            ],
            'icon' => 'bi-hospital',
            'color' => 'primary',
        ],
        'emergency' => [
            'name' => 'Emergency Services',
            'description' => 'Emergency visits, triage, and interventions',
            'tables' => [
                'emergency_interventions',
                'emergency_alerts',
                'triage_assessments',
                'emergency_visits',
            ],
            'icon' => 'bi-lightning',
            'color' => 'danger',
        ],
        'surgery' => [
            'name' => 'Surgery & Theatre',
            'description' => 'Surgical schedules and procedure records (theatre catalog preserved)',
            'tables' => [
                'surgery_teams',
                'surgery_procedures',
                'surgery_schedules',
            ],
            'icon' => 'bi-scissors',
            'color' => 'warning',
        ],
        'blood_bank' => [
            'name' => 'Blood Bank',
            'description' => 'Donations, inventory, and transfusion records',
            'tables' => [
                'transfusions',
                'blood_inventory',
                'blood_donations',
            ],
            'icon' => 'bi-heart',
            'color' => 'danger',
        ],
        'ecommerce' => [
            'name' => 'E-commerce & Store',
            'description' => 'Store orders and deliveries (product catalog preserved)',
            'tables' => [
                'order_items',
                'deliveries',
                'store_orders',
            ],
            'icon' => 'bi-cart',
            'color' => 'success',
        ],
        'teleconsultations' => [
            'name' => 'Teleconsultations',
            'description' => 'Virtual consultations, chats, and uploaded files',
            'tables' => [
                'teleconsultation_files',
                'teleconsultation_chats',
                'teleconsultations',
            ],
            'icon' => 'bi-camera-video',
            'color' => 'secondary',
        ],
        'messaging' => [
            'name' => 'Messages & Conversations',
            'description' => 'Internal messaging and conversation threads',
            'tables' => [
                'messages',
                'conversation_participants',
                'conversations',
            ],
            'icon' => 'bi-chat-left-text',
            'color' => 'secondary',
        ],
        'complaints' => [
            'name' => 'Complaints & Feedback',
            'description' => 'Patient complaints and feedback records',
            'tables' => ['complaints'],
            'icon' => 'bi-chat-dots',
            'color' => 'warning',
        ],
        'notifications' => [
            'name' => 'Notifications',
            'description' => 'System notifications and user notification preferences',
            'tables' => [
                'user_notification_preferences',
                'notifications',
            ],
            'icon' => 'bi-bell',
            'color' => 'secondary',
        ],
        'audit_logs' => [
            'name' => 'Audit Logs & Activity',
            'description' => 'Activity logs, login audit, and sync logs',
            'tables' => [
                'sync_queues',
                'sync_logs',
                'login_audit',
                'activity_logs',
            ],
            'icon' => 'bi-journal-text',
            'color' => 'dark',
        ],
        'reports' => [
            'name' => 'Reports & Analytics',
            'description' => 'GHS reports and NHIS claims data',
            'tables' => [
                'nhis_claims',
                'ghs_reports',
            ],
            'icon' => 'bi-graph-up',
            'color' => 'info',
        ],
        'workflow' => [
            'name' => 'Workflow Instances',
            'description' => 'Running workflow instances and action logs (workflow definitions preserved)',
            'tables' => [
                'workflow_action_logs',
                'workflow_instances',
            ],
            'icon' => 'bi-diagram-3',
            'color' => 'dark',
        ],
        'staff_attendance' => [
            'name' => 'Staff Attendance',
            'description' => 'Staff attendance records',
            'tables' => ['staff_attendances'],
            'icon' => 'bi-person-check',
            'color' => 'primary',
        ],
    ],

];
