<?php

/**
 * Generates config/permissions.php from seeder permission strings.
 * Run: php scripts/generate-permissions-config.php
 */

$root = dirname(__DIR__);
$files = array_merge(
    glob($root . '/database/seeders/*Permissions*.php') ?: [],
    glob($root . '/database/seeders/*Permission*.php') ?: [],
    [$root . '/database/seeders/RolePermissionSeeder.php']
);

$names = [];
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (!is_string($content)) {
        continue;
    }
    preg_match_all("/'([a-z][a-z0-9_.]+)'/", $content, $matches);
    foreach ($matches[1] as $name) {
        if (preg_match('/^(view|create|edit|delete|manage|process|search|dispense|enter|verify|approve|print|generate|export|call|serve|complete|triage|acknowledge|resolve|perform|upload|receive|mark|send|start|cancel|sign|amend|download|teleconsultation|calculate)/', $name)) {
            $names[$name] = true;
        }
    }
}

// Ensure permissions referenced only in role lists
$extra = ['calculate_insurance_coverage', 'perform_radiology_studies'];
foreach ($extra as $name) {
    $names[$name] = true;
}

ksort($names);

function guessModule(string $name): string
{
    if (str_starts_with($name, 'teleconsultation.')) {
        return 'teleconsultation';
    }

    $actions = [
        'acknowledge_', 'approve_', 'calculate_', 'call_', 'cancel_', 'complete_', 'create_',
        'delete_', 'dispense_', 'download_', 'edit_', 'enter_', 'export_', 'generate_',
        'manage_', 'mark_', 'perform_', 'print_', 'process_', 'receive_', 'resolve_',
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

    return $rest ?: 'general';
}

$modules = [];
foreach (array_keys($names) as $name) {
    $module = guessModule($name);
    $modules[$module][$name] = null;
}

ksort($modules);
foreach ($modules as &$perms) {
    ksort($perms);
}
unset($perms);

$out = "<?php\n\n";
$out .= "/**\n";
$out .= " * Canonical web-guard permission registry.\n";
$out .= " *\n";
$out .= " * HOW TO ADD A NEW PERMISSION\n";
$out .= " * 1. Add the permission name to the appropriate module array below.\n";
$out .= " * 2. Run: php artisan permissions:sync\n";
$out .= " * 3. Assign to roles via the admin UI (/permissions, /roles) or Refine* seeders.\n";
$out .= " *\n";
$out .= " * Deploy: include `php artisan permissions:sync` after code updates.\n";
$out .= " * Safe to re-run — uses firstOrCreate; never removes existing assignments.\n";
$out .= " *\n";
$out .= " * @see App\\Services\\PermissionSyncService\n";
$out .= " * @see Database\\Seeders\\DatabaseSeeder Permission seeding documentation\n";
$out .= " */\n\n";
$out .= "return [\n\n";
$out .= "    'guard' => 'web',\n\n";
$out .= "    'modules' => [\n";

foreach ($modules as $module => $perms) {
    $out .= "        '{$module}' => [\n";
    foreach ($perms as $name => $desc) {
        $out .= "            '{$name}' => null,\n";
    }
    $out .= "        ],\n\n";
}

$out .= "    ],\n\n";
$out .= "];\n";

file_put_contents($root . '/config/permissions.php', $out);
echo 'Wrote ' . count($names) . ' permissions across ' . count($modules) . " modules to config/permissions.php\n";
