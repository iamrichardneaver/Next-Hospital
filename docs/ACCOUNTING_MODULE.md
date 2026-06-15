# Hospital Accounting Module — Feature Matrix

Last updated: 2026-06-06

## Role model

| Role | Scope | Canonical seeder |
|------|--------|------------------|
| **Cashier** | Counter operations — collect payments, create invoices, debtor payments | `RefineCashierPermissionsSeeder` (17 permissions) |
| **Accountant** | Financial oversight — billing admin, reports, pricing, insurance claims, analytics, full accounting hub | `RefineAccountantPermissionsSeeder` (58 permissions) |

Demo user: `accountant@nexthospital.com` / `password123`

Accounting hub: `/accounting` (permission: `view_financial_dashboard`)

---

## Feature matrix

| Feature | Status | Notes |
|---------|--------|-------|
| Invoices / billing CRUD | **Working** | `BillingController`, routes `billing.*` |
| Payment recording | **Working** | `PaymentService`, cashier + billing record-payment |
| Payment methods tracking | **Working** | `payments.payment_method`, expanded columns migration |
| Debtors & aging | **Working** | `DebtorController`, `DebtorService` |
| Revenue analytics | **Working** | `/revenue`, department/branch breakdown |
| Financial summary report | **Working** | `/reports/financial` |
| Cashier daily report | **Working** | `/cashier/daily-report` |
| Accounting dashboard | **Working** | `/accounting` — KPIs, net income, module nav |
| Revenue streams | **Working** | `/accounting/revenue` — service module breakdown from `revenue_transactions` |
| Expenses management | **Working** | `/accounting/expenses` — CRUD, categories, approval workflow |
| Balance sheet | **Working** | `/accounting/balance-sheet` — simplified hospital snapshot |
| Cash flow statement | **Working** | `/accounting/cash-flow` — operating inflows/outflows |
| Revenue vs expenses | **Working** | `/accounting/revenue-vs-expenses` — monthly/quarterly comparison |
| Service pricing | **Working** | `ServicePricingController` |
| Insurance / NHIS claims | **Working** | Insurance module + `/reports/nhis` |
| GHS regulatory reports | **Working** | `/reports/ghs` |
| E-commerce revenue | **Partial** | Rolls into invoices/payments |
| Pharmacy / lab / radiology billing hooks | **Working** | Charges flow to invoices via module billing services |
| InvoiceObserver (no double-count) | **Working** | Revenue via `PaymentObserver` only |
| Branch-level revenue | **Working** | Branch scoping on all accounting reports |
| Payment refunds | **Gap** | `PaymentService::refundPayment()` exists; no route/UI |
| General ledger / chart of accounts | **Gap** | No `accounts` or `journal_entries` tables |
| Journal entries | **Gap** | Not implemented |
| Trial balance / formal GAAP P&L | **Partial** | Revenue vs expenses + simplified balance sheet |
| Tax/VAT line items on invoices | **Partial** | Settings permissions seeded; no dedicated tax engine |

---

## Routes

| Path | Route name | Permission |
|------|------------|------------|
| `/accounting` | `accounting.index` | `view_financial_dashboard` |
| `/accounting/revenue` | `accounting.revenue` | `view_revenue_reports` |
| `/accounting/expenses` | `accounting.expenses.index` | `view_expenses` |
| `/accounting/expenses/create` | `accounting.expenses.create` | `manage_expenses` |
| `/accounting/balance-sheet` | `accounting.balance-sheet` | `view_balance_sheet` |
| `/accounting/cash-flow` | `accounting.cash-flow` | `view_cash_flow` |
| `/accounting/revenue-vs-expenses` | `accounting.revenue-vs-expenses` | `view_revenue_reports` |

---

## Revenue calculation

Revenue is **not duplicated** from billing. It flows:

1. Patient pays invoice → `Payment` created (status `completed`)
2. `PaymentObserver` creates `RevenueTransaction` with `service_type` derived from invoice line items
3. All accounting revenue reports aggregate `revenue_transactions` where `status = completed`

Service types: `consultation`, `lab`, `pharmacy`, `imaging`, `surgery`, `ward`, `ecommerce`, `insurance`, `other`.

---

## Expenses schema

- `expense_categories` — name, code, is_active (seeded via `ExpenseCategorySeeder`)
- `expenses` — category, branch, amount, date, description, reference, payment_method, status (`draft|pending|approved|rejected|paid`), approval audit fields

Approval workflow: create → `pending` → approve/reject → `paid` (optional).

---

## Files (accounting module)

- `app/Services/AccountingReportService.php`
- `app/Http/Controllers/Web/AccountingController.php`
- `app/Http/Controllers/Web/ExpenseController.php`
- `app/Models/Expense.php`, `ExpenseCategory.php`
- `database/migrations/2026_06_06_100001_create_expense_categories_table.php`
- `database/migrations/2026_06_06_100002_create_expenses_table.php`
- `database/seeders/ExpenseCategorySeeder.php`
- `database/seeders/RefineAccountantPermissionsSeeder.php`
- `resources/views/accounting/*`
- `resources/views/layouts/sidebar.blade.php` — accounting submenu
- `app/Services/ReportCatalog.php`

---

## Setup

```bash
php artisan migrate
php artisan db:seed --class=ExpenseCategorySeeder
php artisan db:seed --class=RefineAccountantPermissionsSeeder
```

---

## Test checklist (`accountant@nexthospital.com`)

1. Login → accountant dashboard loads.
2. Sidebar Accounting submenu: Hub, Revenue, Expenses, Balance Sheet, Cash Flow, Revenue vs Expenses.
3. `/accounting` — KPIs: total revenue, expenses, net income, receivables.
4. `/accounting/revenue` — pie/bar charts from real `revenue_transactions`.
5. `/accounting/expenses/create` — record expense → appears in list and reports after approval.
6. `/accounting/balance-sheet` — assets/liabilities/equity render without error.
7. `/accounting/cash-flow` — operating inflows/outflows chart loads.
8. `/accounting/revenue-vs-expenses` — comparison table and chart work.

---

## Limitations vs full ERP

- No double-entry general ledger or chart of accounts
- Balance sheet is simplified (cash, receivables, payables, retained earnings)
- Investing/financing cash flow sections are placeholders
- No automated bank reconciliation
- Expenses require manual entry (not synced from procurement)
