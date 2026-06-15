# NextHospital Revenue Audit

**Date:** 2026-06-07  
**Canonical source of truth:** `revenue_transactions` where `status = 'completed'`

---

## Revenue Formula (Authoritative)

```
Total Revenue = SUM(revenue_transactions.amount)
              WHERE status = 'completed'
              AND transaction_date BETWEEN start_date AND end_date
              AND branch_id = :branch (when scoped)

By stream   = GROUP BY service_type
By method   = GROUP BY payment_method
```

**Not revenue until paid:** `PendingChargesService` charges, unpaid/partial invoices, pending payments.

---

## Data Flow Paths

| # | Path | Formula / Source | Bug Risk |
|---|------|------------------|----------|
| 1 | `PaymentObserver` → `revenue_transactions` | One row per completed `Payment`; `service_type` from first invoice line item | Fixed: creates row when payment updated to `completed` (was missing before) |
| 2 | `Payment` model | `SUM(amount) WHERE status='completed'` — used by Cashier, balance sheet cash | Aligns with revenue_transactions if observer runs |
| 3 | `Invoice` paid amounts | Tracks `paid_amount` / `payment_status`; **not** used for dashboard revenue anymore | Was incorrectly used for dashboard monthly revenue (fixed) |
| 4 | `AccountingReportService` | `revenueQuery()` → `revenue_transactions` | Correct |
| 5 | `PendingChargesService` | Unbilled charges only | Correctly excluded |
| 6 | Module billing | Invoice items carry `service_type`; mapped in `PaymentObserver::determineServiceTypeFromInvoice()` | Multi-line invoices use first item only |
| 7 | `ReportCatalog` / accounting hub | Uses `AccountingReportService` | Correct |
| 8 | Cashier daily report | `Payment::completed` sums | Uses `payment_date`/`created_at` — consistent with cash collected |
| 9 | API `BillingController::getStatistics` | **Still uses `Invoice::sum(total_amount)`** | Known discrepancy — accounting hub is authoritative |
| 10 | Mobile billing | Via `PaymentService` → `PaymentObserver` | Correct when payment completes |

---

## Bugs Found & Fixed

1. **Dashboard used invoice `total_amount` (paid status)** instead of `revenue_transactions` — fixed in `DashboardStatsService` / `RevenueReportService`.
2. **PaymentObserver** did not create `revenue_transactions` when payment transitioned to `completed` on update — fixed.
3. **`InvoiceObserver`** previously could double-count; now intentionally does **not** create revenue on invoice creation (payment-only).
4. **`FinancialIntegrationTest`** expected invoice-level revenue row — updated to match payment-only model.
5. **`getTotalRevenue`** required date range — now accepts `null` dates for all-time (admin inception totals).

---

## Role → Revenue Scope Matrix

| Role / Permission | Dashboard Revenue | Date Scope | Module Scope |
|-------------------|-------------------|------------|--------------|
| `super_admin`, `admin` | Total Revenue (All Time) + Today card | Inception + today | All streams |
| `view_financial_reports` (incl. accountant) | Total Revenue (All Time) + Today card | Inception + today | All streams (branch) |
| Pharmacist (`dispense_drugs`) | Today's Pharmacy Revenue | Today only | `pharmacy` |
| Lab tech (`process_lab_requests` / `enter_lab_results`) | Today's Laboratory Revenue | Today only | `lab` |
| Radiologist (`process_radiology_*`) | Today's Radiology Revenue | Today only | `imaging` |
| Doctor (`create_consultations` + `create_prescriptions`) | Today's Consultation Revenue | Today only | `consultation` filtered by `doctor_id` via consultation `invoice_id` |
| Receptionist (`view_appointments` only) | Today's Consultations Revenue | Today only | `consultation` (branch, not doctor-scoped) |
| Nurse (no billing permissions) | Hidden | — | — |

### Accountant Decision

Accountants with `view_financial_reports` receive **the same inception-level totals as admin** on the dashboard, because their job requires full branch financial visibility. Operational staff without that permission see **today-only, module-scoped** figures that reset daily.

---

## Report URLs

| Report | URL | Drill-down |
|--------|-----|------------|
| Accounting hub | `/accounting` | KPIs + stream summary |
| Revenue composition | `/accounting/revenue` | Click service row |
| Drill-down detail | `/accounting/revenue/drill-down/{serviceType}?start_date=&end_date=` | Patient, invoice, amount |
| CSV export | Same drill-down URL + `&export=csv` | — |
| Revenue vs expenses | `/accounting/revenue-vs-expenses` | Period comparison |
| Cash flow | `/accounting/cash-flow` | Daily flows from `revenue_transactions` |

---

## Files Changed

- `app/Services/RevenueReportService.php` (new)
- `app/Services/AccountingReportService.php`
- `app/Services/DashboardStatsService.php`
- `app/Observers/PaymentObserver.php`
- `app/Models/User.php` — `isAdminOrSuperAdmin()`
- `app/Http/Controllers/Web/DashboardController.php`
- `app/Http/Controllers/Web/AccountingController.php`
- `routes/web.php`
- `resources/views/dashboard/index.blade.php`
- `resources/views/accounting/revenue.blade.php`
- `resources/views/accounting/revenue-drill-down.blade.php` (new)
- `tests/Feature/FinancialIntegrationTest.php`
- `tests/Feature/RevenueDashboardScopingTest.php` (new)

---

## Test Steps Per Role

### Admin
1. Login as admin → Dashboard shows **Total Revenue (All Time)** and **Today's Revenue**.
2. Compare: `php artisan tinker` → `RevenueTransaction::where('status','completed')->sum('amount')` matches all-time card.
3. Open `/accounting/revenue` → sum of streams equals period total.

### Pharmacist
1. Create pharmacy payment today and lab payment today.
2. Dashboard shows **only pharmacy today** amount.
3. `/dashboard/realtime-data` JSON `statistics.revenue_amount` matches pharmacist scope.

### Lab scientist
1. Same as pharmacist but lab stream only.

### Doctor
1. Create consultation invoice linked to doctor's consultation (`invoice_id` on consultation).
2. Dashboard shows only that doctor's consultation payments today.

### Nurse (no billing)
1. Dashboard has **no revenue card**.

### Accountant (`view_financial_reports`)
1. Same all-time + today cards as admin (branch-scoped).
2. Full reports at `/accounting/*` accessible.

### Tinker verification
```php
$user = User::find(ID);
$branchId = $user->staffProfile->branch_id;
app(\App\Services\RevenueReportService::class)->getDashboardRevenue($user, $branchId);
app(\App\Services\AccountingReportService::class)->getTotalRevenue($branchId, null, null);
```

---

## Remaining Known Issues (Not Changed)

- **API `BillingController::getStatistics`** still sums invoice totals — recommend migrating to `AccountingReportService` in a follow-up.
- **Multi-line invoices** classify entire payment by first line item `service_type`.
- **Cashier `total_collected`** uses `created_at` not `payment_date` for today's total — minor date-boundary difference.
