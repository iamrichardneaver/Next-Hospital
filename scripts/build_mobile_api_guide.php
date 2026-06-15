<?php
/**
 * Build documentation/MOBILE_API_DEVELOPER_GUIDE.md from template + generated routes.
 */
$outPath = dirname(__DIR__) . '/../documentation/MOBILE_API_DEVELOPER_GUIDE.md';
$routesPath = dirname(__DIR__) . '/routes_api_grouped.json';
$appendixPath = dirname(__DIR__) . '/../documentation/_api_endpoints_generated.md';

if (!file_exists($routesPath)) {
    fwrite(STDERR, "Run analyze_api_routes.php first.\n");
    exit(1);
}

// Regenerate appendix if missing
if (!file_exists($appendixPath)) {
    passthru('"' . PHP_BINARY . '" "' . __DIR__ . '/generate_api_guide_endpoints.php" > "' . $appendixPath . '"');
}

$routeCount = count(json_decode(file_get_contents($routesPath), true));
$appendix = file_exists($appendixPath) ? file_get_contents($appendixPath) : '';

$guide = <<<MD
# NextHospital Mobile API — Developer Integration Guide

**Version:** 2026.06.15  
**API routes:** {$routeCount} (verified via \`php artisan route:list --path=api\`)  
**Audience:** Mobile engineers integrating patient, doctor, and staff workflows  
**Backend:** Laravel 11 + Sanctum · JSON REST · MySQL (\`nexthospital\`)

---

## Table of contents

1. [Overview & architecture](#1-overview--architecture)
2. [Base URLs](#2-base-urls)
3. [Authentication flow](#3-authentication-flow)
4. [Common headers](#4-common-headers)
5. [Error response format](#5-error-response-format)
6. [Pagination, filtering & sorting](#6-pagination-filtering--sorting)
7. [Permission & role model](#7-permission--role-model)
8. [Endpoint reference by module](#8-endpoint-reference-by-module)
9. [Paystack payment integration](#9-paystack-payment-integration)
10. [FCM push & device registration](#10-fcm-push--device-registration)
11. [File uploads & PDF downloads](#11-file-uploads--pdf-downloads)
12. [Teleconsultation & Jitsi](#12-teleconsultation--jitsi)
13. [Rate limiting & best practices](#13-rate-limiting--best-practices)
14. [Changelog & versioning](#14-changelog--versioning)
15. [Quick start (curl)](#15-quick-start-curl)
16. [Appendix — complete route index](#appendix--complete-route-index)

**Related docs:** [API_MOBILE_CONTRACT_VERIFICATION.md](./API_MOBILE_CONTRACT_VERIFICATION.md) · [LOCAL_DEV_RUNBOOK.md](./LOCAL_DEV_RUNBOOK.md) · [POSTMAN_MOBILE_API_GUIDE.md](./POSTMAN_MOBILE_API_GUIDE.md) · Postman collection: \`backend/postman_collection.json\`

---

## 1. Overview & architecture

NextHospital exposes a **REST JSON API** under the \`/api\` prefix. All mobile clients authenticate with **Laravel Sanctum** personal access tokens (Bearer). Business logic is shared between Web and API controllers via Services — responses are **dynamic from the database** (no mock data).

| Layer | Location |
|-------|----------|
| Routes | \`backend/routes/api.php\` |
| Controllers | \`backend/app/Http/Controllers/API/\` |
| Validation | Form requests + inline validators |
| Permissions | \`config/permissions.php\` + \`CheckPermission\` middleware |
| Mobile client reference | \`nexthospital-mobile/src/api/services.ts\` |

**Response envelope (standard):**

\`\`\`json
{
  "success": true,
  "message": "Human-readable summary",
  "data": { },
  "meta": { "current_page": 1, "last_page": 5, "per_page": 20, "total": 98 }
}
\`\`\`

List endpoints may return \`data\` as an array or a Laravel paginator (\`data.data\`, \`current_page\`, etc.). The mobile app normalizes both shapes.

---

## 2. Base URLs

| Environment | Base URL | Notes |
|-------------|----------|-------|
| **Production** | \`https://portal.omanyeclinic.com/api\` | Production portal |
| **XAMPP (Apache)** | \`http://localhost/nexthospital/backend/public/api\` | Windows local stack |
| **Artisan serve** | \`http://localhost:8000/api\` | \`php artisan serve\` from \`backend/\` |
| **Android emulator** | \`http://10.0.2.2/nexthospital/backend/public/api\` | Host loopback via emulator bridge |
| **Physical device (LAN)** | \`http://<YOUR_LAN_IP>/nexthospital/backend/public/api\` | Same Wi‑Fi as dev machine |

**Runtime discovery:** \`GET /settings/api/mobile-config\` (public) returns \`{ "success": true, "data": { "api_url": "..." } }\`. The mobile app bootstraps from build-time URL, then persists \`api_url\` from this endpoint.

**Branding (public):** \`GET /settings/mobile-app\` — logos, colors, app name for dynamic theming.

**Branches (public):** \`GET /branches/public\` — active branches for registration/booking.

---

## 3. Authentication flow

### 3.1 Patient registration

\`POST /auth/register-patient\` · **Public**

| Field | Type | Required |
|-------|------|----------|
| \`first_name\` | string | yes |
| \`last_name\` | string | yes |
| \`email\` | string | yes, unique |
| \`password\` | string | yes, min 8 |
| \`password_confirmation\` | string | yes |
| \`phone\` | string | yes |
| \`gender\` | \`Male\` \| \`Female\` | yes |
| \`branch_id\` | integer | yes |

**Success (201):**

\`\`\`json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": { "id": 42, "patient_id": 15, "email": "patient@example.com", "roles": [{ "name": "patient" }] },
    "access_token": "1|...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
\`\`\`

**Errors:** \`422\` validation, \`409\` duplicate patient/email.

### 3.2 Login

\`POST /auth/login\` · **Public**

\`\`\`json
{ "email": "user@example.com", "password": "secret" }
\`\`\`

Returns \`data.access_token\` and \`data.user\`. For patients, \`user.patient_id\` is the **patients table id** (use for orders/billing, not \`user.id\`).

Staff login uses the same endpoint; roles and permissions are included in \`user.roles\`.

### 3.3 Current user

\`GET /auth/me\` · **Bearer**

Alias: \`GET /auth/user\`. Patient profile: \`GET /patients/me\`, \`PUT /patients/me\` (must be registered **before** \`apiResource('patients')\` in routes).

### 3.4 Logout

\`POST /auth/logout\` · **Bearer**

Optional body: \`{ "device_id": "uuid" }\` — deactivates FCM device row.

### 3.5 Password reset (OTP)

| Step | Method | Path |
|------|--------|------|
| Request OTP | POST | \`/auth/forgot-password\` |
| Reset | POST | \`/auth/reset-password\` |

\`forgot-password\` accepts \`{ "email": "..." }\`. When mail is configured, OTP is emailed. In dev (\`APP_DEBUG=true\`, mail driver \`log\`), OTP may appear in the JSON response for testing only.

\`reset-password\` accepts \`email\`, \`otp\`, \`password\`, \`password_confirmation\`.

### 3.6 Token refresh

Sanctum personal tokens do not auto-refresh. On \`401\`, re-authenticate. Tokens expire per Sanctum config (default long-lived); \`expires_in: 3600\` in login response is advisory for client UX.

---

## 4. Common headers

| Header | Value | When |
|--------|-------|------|
| \`Accept\` | \`application/json\` | Always |
| \`Content-Type\` | \`application/json\` | POST/PUT/PATCH with body |
| \`Authorization\` | \`Bearer {access_token}\` | Protected routes |
| \`X-Device-Platform\` | \`android\` \| \`ios\` | Recommended on login/device register |

PDF/binary downloads: send \`Authorization\` only; omit \`Accept: application/json\` if the client library forces JSON parsing.

---

## 5. Error response format

| HTTP | Meaning |
|------|---------|
| \`200\` | Success |
| \`201\` | Created |
| \`401\` | Missing/invalid token |
| \`403\` | Authenticated but permission denied |
| \`404\` | Resource not found |
| \`422\` | Validation failed |
| \`429\` | Rate limited |
| \`500\` | Server error |

\`\`\`json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "email": ["The email field is required."]
  }
}
\`\`\`

Mobile clients should surface \`message\` first, then field errors from \`errors\`.

---

## 6. Pagination, filtering & sorting

**Query parameters (common):**

| Param | Type | Description |
|-------|------|-------------|
| \`page\` | int | Page number (default 1) |
| \`per_page\` | int | Items per page (default 15–20) |
| \`search\` | string | Text search where supported |
| \`status\` | string | Entity-specific filter |
| \`branch_id\` | int | Branch scope |
| \`start_date\` / \`end_date\` | \`Y-m-d\` | Date range |
| \`sort_by\` / \`sort_order\` | string | \`asc\` \| \`desc\` (store-items, etc.) |

**Pagination meta:**

\`\`\`json
"meta": { "current_page": 1, "last_page": 4, "per_page": 20, "total": 72 }
\`\`\`

---

## 7. Permission & role model

Roles (\`roles\` table): \`patient\`, \`doctor\`, \`nurse\`, \`admin\`, \`pharmacist\`, \`receptionist\`, \`lab_technician\`, \`cashier\`, etc.

| Role | Typical mobile access |
|------|----------------------|
| **patient** | Own profile, appointments, lab results, invoices, store, teleconsultation join |
| **doctor** | Queue, consultations, prescriptions, schedules, teleconsultation |
| **staff** | Module-specific via permission names |

Permissions are enforced server-side (\`permission:view_lab_requests\`, etc.). Login response includes role permission names for UI gating — **always trust API 403**, not client-only checks.

Staff permission sync (admin): \`GET /permissions/sync/status\`, \`POST /permissions/sync\`.

---

## 8. Endpoint reference by module

Below are **mobile-critical** endpoints with request/response contracts. For the full **899-route** index see [Appendix](#appendix--complete-route-index).

### 8.1 Auth & config

| Method | Path | Auth | Notes |
|--------|------|------|-------|
| POST | \`/auth/login\` | Public | Returns token + user |
| POST | \`/auth/register-patient\` | Public | Patient self-signup |
| POST | \`/auth/logout\` | Bearer | Optional \`device_id\` |
| GET | \`/auth/me\` | Bearer | Current user |
| POST | \`/auth/forgot-password\` | Public | OTP email |
| POST | \`/auth/reset-password\` | Public | OTP + new password |
| GET | \`/settings/api/mobile-config\` | Public | Runtime \`api_url\` |
| GET | \`/settings/mobile-app\` | Public | Branding |
| GET | \`/app-version/check\` | Public | Force-update check |
| GET | \`/branches/public\` | Public | Branch list |

### 8.2 Patients & profile

| Method | Path | Auth | Permission |
|--------|------|------|------------|
| GET | \`/patients/me\` | Bearer | patient role |
| PUT | \`/patients/me\` | Bearer | patient role |
| GET | \`/patients/{id}/comprehensive-profile\` | Bearer | scoped |
| GET | \`/patients/{id}/timeline\` | Bearer | scoped |
| GET | \`/patient/allergies\` | Bearer | own |
| GET | \`/patient/medical-history\` | Bearer | own |
| GET/POST/PUT/DELETE | \`/patient/dependents\` | Bearer | own |

### 8.3 Appointments

| Method | Path | Auth | Notes |
|--------|------|------|-------|
| GET | \`/appointments/available-dates\` | Public | \`doctor_id\`, \`month\`, \`year\`, optional \`branch_id\`, \`appointment_type\` |
| GET | \`/appointments/available-time-slots\` | Public | \`doctor_id\`, \`branch_id\`, \`date\`, \`appointment_type\` |
| GET | \`/appointments/cost-estimate\` | Bearer | Optional \`slot_id\` for exact fee |
| POST | \`/appointments\` | Bearer | Book appointment |
| GET | \`/appointments/user\` | Bearer | Patient list |
| GET | \`/appointments/upcoming\` | Bearer | \`days_ahead\` |
| POST | \`/appointments/{id}/cancel\` | Bearer | Cancel |
| POST | \`/patient/appointments/{id}/reschedule\` | Bearer | \`appointment_date\`, \`appointment_time\` |
| POST | \`/appointments/{id}/join-virtual\` | Bearer | Jitsi config |
| POST | \`/appointments/paystack/initialize\` | Bearer | Paystack init |
| POST | \`/appointments/paystack/process\` | Bearer | Verify payment |
| GET | \`/users/doctors\` | Bearer | Doctor search |
| GET | \`/doctors/{id}/detail\` | Public | Doctor profile |

**Book appointment body:**

\`\`\`json
{
  "doctor_id": 3,
  "branch_id": 1,
  "appointment_date": "2026-06-20",
  "appointment_time": "09:00",
  "appointment_type": "in-person",
  "slot_id": 12,
  "reason": "Follow-up"
}
\`\`\`

### 8.4 Consultations & queue (doctor/staff)

| Method | Path | Permission |
|--------|------|------------|
| GET | \`/consultations/doctor/queue\` | doctor queue |
| POST | \`/consultations/call-next\` | \`branch_id\` in body |
| POST | \`/consultations\` | create consultation |
| PUT | \`/consultations/{id}\` | update / complete / order labs & radiology |
| GET | \`/consultations/{id}/lab-requests\` | linked lab orders |
| GET | \`/consultations/{id}/scans\` | linked radiology |
| GET | \`/consultations/doctor/completed\` | doctor history |
| POST | \`/vitals\` | record vitals |
| GET | \`/vitals/me\` | patient vitals |

### 8.5 Laboratory

| Method | Path | Notes |
|--------|------|-------|
| GET | \`/lab/patient-results\` | Patient completed results |
| GET | \`/lab/results/{id}\` | Result detail |
| GET | \`/lab/requests/{requestId}/pdf\` | PDF download (Bearer) |
| GET | \`/lab/lab-test-types\` | Search test types (\`search\` query) |

Staff: full \`/lab/*\` CRUD, queue, result entry, verification — see appendix (\`lab\`: 104 routes).

### 8.6 Radiology

| Method | Path | Notes |
|--------|------|-------|
| GET | \`/radiology/requests\` | List requests |
| GET | \`/radiology/modalities\` | Modalities for ordering |
| GET | \`/radiology/departments\` | Departments |

### 8.7 Pharmacy & e-commerce

| Method | Path | Notes |
|--------|------|-------|
| GET | \`/store-items\` | Product catalog |
| GET | \`/store-items/categories\` | Categories |
| GET/POST/PUT/DELETE | \`/patient/cart/*\` | Cart CRUD |
| GET | \`/patient/cart/summary\` | \`delivery_method\`: pickup \| delivery |
| POST | \`/store-orders\` | Checkout — use **patient_id** from auth |
| POST | \`/ecommerce/orders/paystack/initialize\` | Order payment |
| POST | \`/ecommerce/orders/paystack/process\` | Confirm payment |
| GET | \`/prescriptions/active\` | Active prescriptions |
| GET | \`/prescriptions/history\` | History |
| POST | \`/prescriptions/refill-request\` | Refill request |

### 8.8 Billing & payments

| Method | Path | Notes |
|--------|------|-------|
| GET | \`/billing/summary\` | Outstanding/paid totals |
| GET | \`/billing/invoices\` | Paginated invoices |
| GET | \`/invoices/{id}\` | Invoice detail |
| GET | \`/invoices/{id}/pdf\` | Invoice PDF |
| GET | \`/invoices/{id}/receipt-pdf\` | Receipt PDF |
| GET | \`/billing/payments\` | Payment history |
| POST | \`/billing/payments/paystack/initialize\` | Invoice Paystack |
| POST | \`/billing/payments/paystack\` | Process invoice payment |
| POST | \`/billing/payments/paystack/verify\` | Verify reference |
| POST | \`/payments/{payment}/refund\` | Staff refund (\`process_payments\`) |

### 8.9 Insurance

| Method | Path | Audience |
|--------|------|----------|
| GET | \`/patient/insurance/providers\` | Patient |
| GET | \`/patient/insurance/policies\` | Patient |
| POST | \`/patient/insurance/policies\` | Patient register policy |
| GET | \`/billing/insurance-claims\` | Patient claims |
| POST | \`/billing/insurance-claims\` | Submit claim |

Staff admin: \`/insurance-providers\`, \`/insurance-policies\`, \`/insurance-claims\`, \`/pre-authorizations\` — see appendix.

### 8.10 Notifications & devices

| Method | Path | Notes |
|--------|------|-------|
| POST | \`/devices/register\` | FCM token registration |
| POST | \`/devices/unregister\` | On logout |
| GET | \`/notifications\` | \`status\`: unread \| read |
| GET | \`/notifications/unread-count\` | Badge count |
| PUT | \`/notifications/{id}/read\` | Mark read |
| DELETE | \`/notifications/{id}\` | Delete |

### 8.11 Teleconsultation & chat

| Method | Path | Notes |
|--------|------|-------|
| GET/POST | \`/teleconsultations\` | List / create |
| GET | \`/teleconsultations/{id}\` | Detail |
| POST | \`/teleconsultations/{id}/start\` | Start session |
| POST | \`/teleconsultations/{id}/end\` | End session |
| GET | \`/teleconsultations/{id}/jitsi-config\` | Jitsi room + JWT |
| GET/POST | \`/teleconsultations/{id}/chat\` | Session chat |
| GET/POST | \`/chat/conversations\` | Support chat |
| POST | \`/chat/messages\` | Send message |

### 8.12 Expenses & accounting (staff)

| Method | Path | Permission |
|--------|------|------------|
| GET | \`/expenses/categories\` | expense viewers |
| GET | \`/expenses/my\` | own submissions |
| POST | \`/expenses\` | \`create_expenses\` |
| GET | \`/expenses\` | list (approver) |
| POST | \`/expenses/{id}/approve\` | \`approve_expenses\` |
| POST | \`/expenses/{id}/reject\` | \`approve_expenses\` + \`rejection_reason\` |
| GET | \`/accounting/dashboard\` | financial dashboard |
| GET | \`/accounting/revenue\` | \`?export=csv\` or \`?export=pdf\` |
| GET | \`/accounting/revenue-vs-expenses\` | exports supported |
| GET | \`/accounting/cash-flow\` | exports supported |
| GET | \`/accounting/balance-sheet\` | \`as_of_date\`, exports |

### 8.13 Wards, ICU, surgery, blood bank

| Module | Prefix | Key routes |
|--------|--------|------------|
| Wards/beds | \`/wards\`, \`/beds\`, \`/bed-assignments\` | Admission & assignment |
| ICU | \`/icu\` | Admit, vitals, discharge |
| Surgery | \`/surgery-schedules\` | CRUD, start, complete |
| Blood bank | \`/blood-bank/donations\`, \`/inventory\`, \`/transfusions\` | Full CRUD |

### 8.14 Operations & compliance

| Module | Prefix | Notes |
|--------|--------|-------|
| Stock counts | \`/stock-counts\` | Pharmacy/lab cycle count |
| Audit (read-only) | \`/audit/activity-logs\`, \`/audit/login-audits\` | \`view_audit_logs\` |
| Emergency | \`/emergency-visits\`, \`/emergency-alerts\` | ED workflow |
| Walk-ins | \`/walk-ins\` | Front-desk walk-in visits |
| Sync | \`/sync/to-server\`, \`/sync/from-server\` | Offline sync engine |
| NHIS/GHS | \`/nhis-claims\`, \`/ghs-reports\` | Ghana compliance |
| Eye services | \`/eye-services\`, \`/eye-test-requests\`, \`/eye-test-results\` | Ophthalmology |
| Complaints | \`/patient/complaints\` (patient), \`/complaints\` (staff) | Feedback |

---

## 9. Paystack payment integration

Three payment flows share Paystack but use different init/process endpoints:

| Flow | Initialize | Process | Verify |
|------|------------|---------|--------|
| **Appointments** | POST \`/appointments/paystack/initialize\` | POST \`/appointments/paystack/process\` | — |
| **Invoices** | POST \`/billing/payments/paystack/initialize\` | POST \`/billing/payments/paystack\` | POST \`/billing/payments/paystack/verify\` |
| **Store orders** | POST \`/ecommerce/orders/paystack/initialize\` | POST \`/ecommerce/orders/paystack/process\` | GET \`/ecommerce/orders/{id}/payment/verify\` |

**Typical invoice initialize body:**

\`\`\`json
{
  "amount": 150.00,
  "email": "patient@example.com",
  "reference": "PAY_1718450000_abc123",
  "payment_type": "invoice",
  "reference_id": 42
}
\`\`\`

Initialize response includes Paystack \`authorization_url\` / access code for WebView or SDK. After customer pays, call **process** with \`reference\` and \`email\`.

**Webhooks (server):** \`POST /paystack/webhook\`, callback \`GET /paystack/callback\` — configured via \`APP_URL\` in backend \`.env\`.

---

## 10. FCM push & device registration

After login, register the device:

\`POST /devices/register\` · **Bearer**

\`\`\`json
{
  "device_id": "unique-install-id",
  "fcm_token": "firebase-token",
  "platform": "android",
  "app_version": "1.2.0",
  "device_name": "Pixel 7",
  "os_version": "14"
}
\`\`\`

On logout: \`POST /devices/unregister\` with \`{ "device_id": "..." }\`.

Push payloads are stored in \`/notifications\`; poll or use FCM data messages aligned with notification types from the backend.

---

## 11. File uploads & PDF downloads

**PDF (authenticated GET):**

| Resource | URL |
|----------|-----|
| Lab result | \`/lab/requests/{requestId}/pdf\` |
| Invoice | \`/invoices/{id}/pdf\` |
| Receipt | \`/invoices/{id}/receipt-pdf\` |
| Accounting reports | \`/accounting/revenue?export=pdf\` (and other report paths) |

Send \`Authorization: Bearer {token}\`. Response is \`application/pdf\` bytes — not JSON.

**Uploads:** \`POST /files/upload\` (multipart) and teleconsultation file routes under \`/teleconsultation-files\`.

---

## 12. Teleconsultation & Jitsi

**Patient/doctor join from appointment:**

1. \`POST /appointments/{id}/join-virtual\`
2. Response \`data\`: \`meeting_url\`, \`room_name\`, \`jwt_token\`, \`meeting_id\`

**Dedicated teleconsultation session:**

1. \`GET /teleconsultations/{id}/jitsi-config\`
2. Embed Jitsi using returned JWT + room name (settings from \`/jitsi/settings\` admin config)

---

## 13. Rate limiting & best practices

- Global API throttle applies (\`api\` middleware group). Search: \`throttle:60,1\` on \`/search\`.
- Retry transient network errors with exponential backoff; do **not** retry \`422\`/\`403\`.
- Always use \`patient_id\` from \`/auth/me\` for \`store-orders\`, not \`user.id\`.
- Cache \`/settings/mobile-app\` and \`/branches/public\` briefly; refresh on app launch.
- Use \`per_page\` ≤ 50 for mobile lists.
- Store tokens in secure storage (Keychain/Keystore); never log tokens.

---

## 14. Changelog & versioning

| Date | Change |
|------|--------|
| **2026-06-15** | Audit gap closure: audit logs API, stock counts API, payment refunds API, accounting PDF/CSV exports, auth OTP, surgery/blood bank/ICU API parity |
| **2025-10-22** | Blood bank, ICU, GHS, NHIS API modules added |
| **2025-02-14** | Mobile contract fixes (billing, appointments Paystack, cart shape) — see API_MOBILE_CONTRACT_VERIFICATION.md |

**Version check:** \`GET /app-version/check?platform=android&version=1.0.0\`

---

## 15. Quick start (curl)

Replace \`BASE\` with your API base URL.

\`\`\`bash
BASE="http://localhost/nexthospital/backend/public/api"

# 1. Mobile config (no auth)
curl -s "\$BASE/settings/api/mobile-config" -H "Accept: application/json"

# 2. Login
curl -s -X POST "\$BASE/auth/login" \\
  -H "Accept: application/json" \\
  -H "Content-Type: application/json" \\
  -d '{"email":"patient@example.com","password":"password"}'

# Save token from data.access_token
TOKEN="1|your-token-here"

# 3. Profile
curl -s "\$BASE/auth/me" -H "Authorization: Bearer \$TOKEN" -H "Accept: application/json"

# 4. Available dates (public)
curl -s "\$BASE/appointments/available-dates?doctor_id=3&month=6&year=2026&branch_id=1" \\
  -H "Accept: application/json"

# 5. Book appointment
curl -s -X POST "\$BASE/appointments" \\
  -H "Authorization: Bearer \$TOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{"doctor_id":3,"branch_id":1,"appointment_date":"2026-06-20","appointment_time":"09:00","appointment_type":"in-person","reason":"Checkup"}'
\`\`\`

---

## Appendix — complete route index

Auto-generated from \`php artisan route:list --path=api\` on 2026-06-15. **899 routes.**

{$appendix}

MD;

file_put_contents($outPath, $guide);
echo "Written {$outPath} (" . strlen($guide) . " bytes)\n";
