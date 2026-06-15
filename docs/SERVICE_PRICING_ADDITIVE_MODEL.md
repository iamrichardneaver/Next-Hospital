# Additive Service Pricing Model

## Formula

**Patient Pays = Module Item Price + Module Fee (when configured)**

- Module item prices come from primary sources: lab test types, drug stock, radiology modalities, appointment fees, bed rates, surgery procedures.
- **Module Fees** are administrative charges configured in Service Pricing and stacked on top.
- If nothing is configured for a module, **no charge is created** (zero fallback).

## Pricing Types

| Type | Badge | Behavior |
|------|-------|----------|
| `module_fee` | Module Fee | Additive fee when patient uses assigned module(s) |
| `item_override` | Item Override | Replaces a specific item price (legacy `lab_test_{id}`, `drug_{id}`) |
| `standalone` | Standalone | Fixed charge only (e.g. registration, custom services) |

## Applies On (workflow triggers)

Module fees only auto-apply when the workflow trigger matches `applies_on`:

| Value | When it fires |
|-------|----------------|
| `visit_checkin` | OPD/IPD/Emergency visit check-in — consultation & ward module fees |
| `order_created` | Lab, pharmacy, radiology, or surgery order created |
| `appointment_booked` | Appointment scheduled — **not** on visit check-in |
| `manual` | Cashier/manual invoice only — never in pending charges |

**No double-charging:** A fee with `appointment_booked` does not also fire at `visit_checkin`. If an appointment_booked consultation-style fee exists, visit check-in skips the consultation module fee for the same appointment day/doctor.

When `applies_on` is empty on older rows, defaults are inferred per module (see migration `2026_06_06_000003_backfill_applies_on_module_fees`).

## Appointment vs Consultation

- **Appointment Fee** (native): from `appointment_fees` or slot fee — `charge_component: module_price`.
- **Appointment module fee**: `applies_on = appointment_booked` — stacks on booking.
- **Consultation module fee**: `applies_on = visit_checkin` — stacks at OPD check-in.
- These are independent unless both are explicitly configured; duplicate prevention applies when an unpaid appointment already carries the booking fee.

## Pending charge line fields

Each line in `GET /billing/pending-charges` and `GET /billing/summary` includes:

| Field | Label (UI) | Meaning |
|-------|------------|---------|
| `base_amount` | — | List price before rules |
| `discount_amount` | Discount | Scheme/rule reductions |
| `insurance_coverage` | Insurance | Payer portion |
| `patient_copay` | Your Portion | What patient pays |
| `final_amount` | — | Same as copay |
| `amount` | — | Patient-facing total (equals copay) |
| `charge_component` | Item Price / Service Fee | `module_price` or `admin_fee` |

## Surgery & Ward

- **Surgery**: procedure price (`PROC_*` / `SURGERY_*` when configured) + `surgery` module fee (`order_created`).
- **Ward**: bed rate (`BED_{type}` when configured) + `ward` module fee (`visit_checkin` on admission).
- Appear in cashier pending charges when surgery is scheduled/completed or ward admission is active (no invoice yet).

## Legacy item overrides

`item_override` rows (`lab_test_{id}`, `drug_{id}`, `radiology_{id}`) **replace** the catalog item price. Module fees still stack separately when configured.

```bash
php artisan pricing:migrate-overrides --dry-run
```

## API Endpoints

| Endpoint | Notes |
|----------|-------|
| `GET /billing/summary` | `pending_charges[]` with full pricing breakdown |
| `GET /billing/pending-charges` | Split lines with insurance/copay |
| `GET /appointments/cost-estimate` | Native fee + `appointment_booked` module fees |
| `POST /pricing/calculate-lab-test` | Respects `order_created` module fees in preview |
| `POST /pricing/calculate-consultation-fee` | Respects `visit_checkin` only |

## Examples

| Scenario | Lines | Patient pays |
|----------|-------|--------------|
| Lab fee GHS 20 + LFT GHS 100 (insured 80%) | LFT + Lab Service Fee | LFT copay GHS 20 + fee GHS 20 |
| Appointment slot GHS 50 + booking fee GHS 10 | Appointment + Service Fee | GHS 60 at booking |
| OPD check-in after booked appointment | Consultation fee skipped if booking fee covers | No duplicate |
