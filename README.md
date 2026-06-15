# NextHospital Backend

Laravel REST API + web admin for **NextHospital** — an enterprise hospital management system built for clinics and hospitals in Ghana, with NHIS/GHS compliance, multi-branch support, and mobile app integration.

---

## Platform overview

| Layer | Description |
|-------|-------------|
| **Web admin** | Bootstrap 5 Blade UI for reception, clinical, lab, pharmacy, billing, and management staff |
| **REST API** | Sanctum-authenticated JSON API for Flutter mobile apps (patient + doctor) |
| **Shared core** | Models, services, permissions, and business logic used by both web and API |

**Tech stack:** PHP 8.2 · Laravel · MySQL 8 · Sanctum · Spatie Permissions · Jitsi (teleconsultation) · Firebase (push notifications)

---

## Modules & features

### Patient & front desk

- **Patient management** — registration, search (NHIS, name, contact), duplicate checks, portal access, export
- **Visit workflow** — OPD, IPD, Lab-only, Pharmacy-only visit tokens and routing
- **Walk-ins** — direct lab/pharmacy service without full OPD flow
- **Queue system** — OPD, lab, pharmacy, emergency, and billing queues with real-time updates
- **Vitals (CVS)** — blood pressure, pulse, temperature, SpO₂, height, weight, BMI

### Clinical & records

- **Appointments** — scheduling, doctor availability, appointment slots, fees, in-person & teleconsultation
- **Consultations** — chief complaint, HPI, diagnoses (ICD codes), progress/discharge notes
- **Consultation interventions** — medications, lab/imaging orders, procedures, referrals, counseling
- **Follow-ups & referrals** — specialist referrals with urgency tracking
- **Patient allergies & medical history** — allergy alerts during prescribing
- **Doctor schedules** — availability and slot management
- **Teleconsultation** — Jitsi video calls with chat and file sharing
- **Eye services** — eye test templates, parameters, requests, and results

### Laboratory & imaging

- **Lab management** — test catalog, categories, templates, parameters, reference ranges, critical values
- **Lab workflow** — requests, sample collection, result entry, QC, PDF reports
- **Lab inventory** — reagents, consumables, equipment, suppliers, purchases, stock counts
- **Radiology** — studies, reports, protocols, equipment, departments, DICOM/image handling
- **Radiology inventory** — contrast agents, suppliers, purchases

### Pharmacy & e-commerce

- **Pharmacy** — drug inventory, prescriptions, dispensing, reorder levels, expiry tracking
- **Pharmacy queue & visits** — walk-in and OPD pharmacy routing
- **Drug orders & suppliers** — purchase orders and supplier management
- **E-commerce / online store** — browse drugs & services, cart, checkout, home delivery
- **Store orders** — order tracking and pharmacy fulfillment

### Inpatient & critical care

- **Wards & beds** — ward types, bed status (occupied/vacant/reserved), assignments
- **ICU** — admission logs, continuous vitals, GCS, ventilator/dialysis, fluid balance
- **Surgery** — scheduling, theatres, surgical teams, procedure codes
- **Emergency** — emergency visits, triage queue, alerts, crash cart
- **Blood bank** — donations, inventory by blood type, transfusions, cross-matching

### Billing, finance & insurance

- **Billing & invoices** — service pricing, invoice generation, multi-method payments
- **Cashier** — payment collection queue
- **Payments** — cash, card, mobile money (MTN/AirtelTigo/Vodafone), bank transfer, insurance
- **Debtors** — outstanding balance tracking and follow-up
- **Revenue & accounting** — revenue transactions, financial reports, expense categories
- **Insurance** — providers, policies, claims, pre-authorizations, co-pay calculation
- **NHIS claims** — submission, vetting, query management, payment reconciliation
- **Paystack / Hubtel** — payment gateway integration (configurable)

### Reporting & compliance

- **Reports hub** — operational and financial analytics
- **GHS reporting** — disease surveillance, maternal/child health, immunization, births & deaths
- **NHIS reporting** — claim batches and compliance exports
- **Audit trail** — activity logs and login audit
- **Complaints** — patient/staff complaint tracking

### Administration & platform

- **Multi-branch** — branches, facility users, branch-specific settings
- **Users & staff** — roles, staff profiles, departments, specializations
- **RBAC** — granular permissions (Spatie), role sync, permission scanner
- **Workflow engine** — configurable clinical/admin workflows with step transitions
- **Settings** — system, branding, email, SMS, API, mobile app, document & print settings
- **Notifications** — in-app, email, SMS, push (Firebase), user preferences
- **Real-time sync** — offline-capable mobile sync queues and device tracking
- **App versions** — mobile app version management and force-update control
- **Chat & messaging** — staff/patient messaging and teleconsultation chat

---

## Hospital workflow scenarios

| Scenario | Flow |
|----------|------|
| **OPD** | Register → queue → vitals → doctor consult → lab/pharmacy → billing → discharge |
| **IPD** | Consult → admit → bed assignment → ongoing care → discharge → final bill |
| **Lab walk-in** | Register → lab queue → tests → results → billing |
| **Pharmacy / store** | Browse → order (web/mobile) → pharmacy fulfillment → payment |

---

## API modules

The REST API exposes **80+ module endpoints**, including:

`patients`, `appointments`, `consultations`, `vitals`, `lab`, `radiology`, `pharmacy`, `prescriptions`, `billing`, `invoices`, `payments`, `insurance`, `nhis-claims`, `wards`, `beds`, `icu`, `surgery`, `emergency`, `blood-bank`, `ecommerce`, `teleconsultations`, `jitsi`, `queues`, `visits`, `reports`, `ghs-reports`, `branches`, `users`, `roles`, `permissions`, `notifications`, `sync`, and more.

Mobile app integration uses the same API consumed by the Flutter patient and doctor apps.

---

## Requirements

- PHP 8.2+
- MySQL 8+
- Composer 2.x
- Node.js (optional, for Vite assets)

## Quick start

```powershell
cd backend
copy .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
```

Configure `.env` with your local database credentials. **Never commit `.env`.**

## Sensitive files (never push to Git)

| Path | Reason |
|------|--------|
| `.env`, `.env.backup` | App keys, DB passwords, API secrets |
| `storage/app/firebase/*.json` | Firebase service account private keys |
| `database/seeders/Generated/` | Production DB snapshots (users, tokens, PHI) |
| `storage/app/public/uploads/` | Patient photos & documents |
| `uploads/` | Runtime user uploads |
| `vendor/` | Install via `composer install` |
| `*.zip` | May bundle secrets or full DB dumps |

See `.gitignore` for the full exclusion list.

## Generated seeders

Reference/lookup seeders under `database/seeders/Generated/` are generated from the live database and excluded from this repo. Regenerate them locally — see `database/seeders/Generated/README.md`.

## License

Proprietary — NextCode Systems / Omanye Clinic.
