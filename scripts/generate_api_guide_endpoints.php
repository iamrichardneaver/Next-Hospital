<?php
/**
 * Generate endpoint reference markdown from routes_api_grouped.json
 * Usage: php scripts/generate_api_guide_endpoints.php > ../documentation/_api_endpoints_generated.md
 */
$routes = json_decode(file_get_contents(__DIR__ . '/../routes_api_grouped.json'), true);
if (!$routes) {
    fwrite(STDERR, "Missing routes_api_grouped.json — run analyze_api_routes.php first\n");
    exit(1);
}

// Priority modules for mobile (order in guide)
$moduleOrder = [
    'auth', 'app-version', 'settings', 'branches', 'devices', 'patients', 'patient',
    'appointments', 'appointment-slots', 'appointment-fees', 'doctor-schedules', 'doctors', 'users',
    'consultations', 'vitals', 'queues', 'opd-queue', 'lab', 'radiology', 'prescriptions', 'pharmacy', 'drugs',
    'store-items', 'store-orders', 'patient', 'ecommerce', 'billing', 'invoices', 'payments', 'cashier',
    'insurance', 'insurance-providers', 'insurance-policies', 'insurance-claims', 'pre-authorizations',
    'teleconsultations', 'teleconsultation-chat', 'teleconsultation-files', 'jitsi', 'chat', 'notifications',
    'notification-preferences', 'dashboard', 'visits', 'walk-ins', 'wards', 'beds', 'bed-assignments',
    'icu', 'surgery-schedules', 'surgery', 'blood-bank', 'emergency-visits', 'emergency-alerts', 'emergency-contacts',
    'expenses', 'accounting', 'revenue', 'stock-counts', 'audit', 'complaints', 'sync', 'ghs-reports', 'nhis-claims',
    'eye-services', 'eye-test-requests', 'eye-test-results', 'permissions', 'roles', 'search', 'files', 'workflow',
];

$byGroup = [];
foreach ($routes as $r) {
    $byGroup[$r['group']][] = $r;
}

$printed = [];
foreach ($moduleOrder as $group) {
    if (empty($byGroup[$group])) continue;
    $printed[$group] = true;
    echo "### {$group}\n\n";
    echo "| Method | Path | Auth / Permission |\n";
    echo "|--------|------|-------------------|\n";
    foreach ($byGroup[$group] as $r) {
        $auth = 'Public';
        $mw = $r['middleware'] ?? '';
        if (str_contains($mw, 'Authenticate:sanctum') || str_contains($mw, 'auth:sanctum')) {
            $auth = 'Bearer token';
        }
        if (preg_match('/CheckPermission:([^,\]]+)/', $mw, $m)) {
            $auth .= ' + `' . $m[1] . '`';
        }
        $path = '/' . $r['path'];
        echo '| ' . str_replace('|', ' / ', $r['method']) . ' | `' . $path . '` | ' . $auth . " |\n";
    }
    echo "\n";
}

// Remaining groups
foreach ($byGroup as $group => $items) {
    if (isset($printed[$group])) continue;
    echo "### {$group}\n\n";
    echo "| Method | Path | Auth / Permission |\n";
    echo "|--------|------|-------------------|\n";
    foreach ($items as $r) {
        $auth = 'Public';
        $mw = $r['middleware'] ?? '';
        if (str_contains($mw, 'Authenticate:sanctum') || str_contains($mw, 'auth:sanctum')) {
            $auth = 'Bearer token';
        }
        if (preg_match('/CheckPermission:([^,\]]+)/', $mw, $m)) {
            $auth .= ' + `' . $m[1] . '`';
        }
        $path = '/' . $r['path'];
        echo '| ' . str_replace('|', ' / ', $r['method']) . ' | `' . $path . '` | ' . $auth . " |\n";
    }
    echo "\n";
}
