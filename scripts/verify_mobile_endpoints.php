<?php
/**
 * Verify mobile app endpoints exist in Laravel routes.
 */
$mobileEndpoints = [
    'GET /settings/api/mobile-config',
    'GET /settings/mobile-app',
    'GET /branches/public',
    'POST /auth/login',
    'POST /auth/register-patient',
    'POST /auth/logout',
    'GET /auth/me',
    'POST /devices/register',
    'POST /devices/unregister',
    'GET /patients/me',
    'PUT /patients/me',
    'GET /patient/allergies',
    'GET /patient/medical-history',
    'GET /lab/patient-results',
    'GET /prescriptions/active',
    'GET /prescriptions/history',
    'GET /users/doctors',
    'GET /doctors/{id}/detail',
    'GET /appointments/user',
    'GET /appointments/today',
    'GET /appointments/upcoming',
    'GET /appointments/available-dates',
    'GET /appointments/available-time-slots',
    'POST /appointments',
    'POST /appointments/{id}/cancel',
    'POST /patient/appointments/{id}/reschedule',
    'POST /appointments/{id}/join-virtual',
    'POST /appointments/paystack/initialize',
    'POST /appointments/paystack/process',
    'GET /billing/summary',
    'GET /billing/pending-charges',
    'GET /billing/invoices',
    'GET /appointments/cost-estimate',
    'GET /billing/payments',
    'POST /billing/payments/paystack/initialize',
    'POST /billing/payments/paystack',
    'POST /billing/payments/paystack/verify',
    'GET /store-items',
    'GET /store-items/{id}',
    'GET /store-items/categories',
    'GET /patient/cart/summary',
    'GET /patient/cart',
    'POST /patient/cart/add',
    'PUT /patient/cart/{id}/quantity',
    'DELETE /patient/cart/{id}',
    'DELETE /patient/cart',
    'POST /store-orders',
    'POST /ecommerce/orders/paystack/initialize',
    'POST /ecommerce/orders/paystack/process',
    'GET /ecommerce/orders/{id}/payment/verify',
    'GET /chat/conversations',
    'POST /chat/conversations/support',
    'GET /chat/conversations/{id}/messages',
    'POST /chat/messages',
    'POST /chat/messages/mark-read',
    'GET /notifications',
    'GET /notifications/unread-count',
    'PUT /notifications/{id}/read',
    'DELETE /notifications/{id}',
    'GET /dashboard/doctor',
    'GET /consultations/doctor/queue',
    'POST /consultations/call-next',
    'GET /patients/{id}/consultations',
    'POST /consultations',
    'PUT /consultations/{id}',
    'GET /radiology/requests',
    'GET /doctor-schedules',
    'GET /teleconsultations/{id}',
    'GET /vitals/me',
    'GET /patients/{id}/policies',
    'GET /patient/insurance/policies',
    'GET /patient/insurance/providers',
    'POST /patient/insurance/policies',
    'GET /billing/insurance-claims',
    'POST /billing/insurance-claims',
    'GET /patients/{id}/timeline',
    'POST /vitals',
    'GET /drugs/search',
    'POST /prescriptions/create',
    'GET /consultations/doctor/completed',
    'GET /consultations/{id}/lab-requests',
    'GET /lab/lab-test-types',
    'GET /radiology/modalities',
    'GET /radiology/departments',
    'GET /consultations/{id}/scans',
    'GET /doctor-schedules/weekly-schedule',
    'POST /prescriptions/refill-request',
    'GET /patient/dependents',
    'POST /patient/dependents',
    'PUT /patient/dependents/{id}',
    'DELETE /patient/dependents/{id}',
    'GET /patient/complaints',
    'POST /patient/complaints',
    'GET /expenses/categories',
    'GET /expenses/my',
    'POST /expenses',
    'GET /expenses',
    'GET /expenses/{id}',
    'POST /expenses/{id}/approve',
    'POST /expenses/{id}/reject',
    'GET /accounting/dashboard',
    'GET /accounting/revenue',
    'GET /accounting/revenue-vs-expenses',
    'GET /accounting/cash-flow',
    'GET /accounting/balance-sheet',
];

$routes = json_decode(file_get_contents(__DIR__ . '/../routes_api.json'), true);
$routeMap = [];
foreach ($routes as $r) {
    $uri = $r['uri'] ?? '';
    if (!str_starts_with($uri, 'api/')) continue;
    $path = substr($uri, 4);
    $rawMethod = $r['method'] ?? 'GET';
    $methods = is_array($rawMethod) ? $rawMethod : explode('|', (string) $rawMethod);
    foreach ($methods as $m) {
        $routeMap[strtoupper(trim($m)) . ' ' . $path] = true;
    }
}

function pathMatches(string $pattern, array $routeMap): bool {
    foreach ($routeMap as $key => $_) {
        [$method, $path] = explode(' ', $key, 2);
        [$pMethod, $pPath] = explode(' ', $pattern, 2);
        if (strtoupper($method) !== strtoupper($pMethod)) continue;
        $regex = '#^' . preg_replace('#\{[^}]+\}#', '[^/]+', $pPath) . '$#';
        if (preg_match($regex, $path)) return true;
    }
    return false;
}

$missing = [];
foreach ($mobileEndpoints as $ep) {
    if (!pathMatches($ep, $routeMap)) {
        $missing[] = $ep;
    }
}

if ($missing) {
    echo "MISSING ENDPOINTS:\n";
    foreach ($missing as $m) echo "  - $m\n";
    exit(1);
}
echo "All " . count($mobileEndpoints) . " mobile endpoints verified.\n";
