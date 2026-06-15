<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Visit;
use App\Models\Consultation;
use App\Models\LabRequest;
use App\Models\Prescription;
use App\Models\RadiologyRequest;
use App\Models\Appointment;
use App\Models\BedAssignment;
use App\Models\Invoice;
use App\Models\SurgerySchedule;
use Illuminate\Support\Facades\DB;

class PendingChargesService
{
    protected PricingService $pricingService;
    protected ModulePricingService $modulePricingService;

    public function __construct(
        PricingService $pricingService = null,
        ModulePricingService $modulePricingService = null
    ) {
        $this->pricingService = $pricingService ?? app(PricingService::class);
        $this->modulePricingService = $modulePricingService ?? app(ModulePricingService::class);
    }

    /**
     * Get all pending charges for a patient.
     */
    public function getPatientPendingCharges($patientId, $branchId = null)
    {
        $charges = [];

        $charges = array_merge($charges, $this->getAppointmentCharges($patientId, $branchId));
        $charges = array_merge($charges, $this->getConsultationCharges($patientId, $branchId));
        $charges = array_merge($charges, $this->getLabCharges($patientId, $branchId));
        $charges = array_merge($charges, $this->getPrescriptionCharges($patientId, $branchId));
        $charges = array_merge($charges, $this->getRadiologyCharges($patientId, $branchId));
        $charges = array_merge($charges, $this->getSurgeryCharges($patientId, $branchId));
        $charges = array_merge($charges, $this->getWardCharges($patientId, $branchId));
        $charges = array_merge($charges, $this->getUnpaidInvoiceCharges($patientId, $branchId));

        usort($charges, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $charges;
    }

    private function getAppointmentCharges($patientId, $branchId = null)
    {
        $query = Appointment::where('billing_status', 'pending')
            ->whereIn('status', ['scheduled', 'confirmed']);

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $charges = [];

        foreach ($query->with(['doctor', 'slot'])->get() as $appointment) {
            foreach ($this->modulePricingService->resolveAppointmentBillableLines($appointment) as $line) {
                $line['requires_full_payment'] = true;
                $line['appointment_id'] = $appointment->id;
                $charges[] = $line;
            }
        }

        return $charges;
    }

    private function getConsultationCharges($patientId, $branchId = null)
    {
        $query = Consultation::where('billing_status', 'pending');

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        if ($branchId) {
            $query->whereHas('visit', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $charges = [];

        foreach ($query->with(['doctor', 'visit'])->get() as $consultation) {
            foreach ($this->modulePricingService->resolveConsultationBillableLines($consultation) as $line) {
                $line['requires_full_payment'] = true;
                $line['consultation_id'] = $consultation->id;
                $charges[] = $line;
            }
        }

        return $charges;
    }

    private function getLabCharges($patientId, $branchId = null)
    {
        $query = LabRequest::where('billing_status', 'pending')
            ->whereIn('status', ['pending', 'completed']);

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $charges = [];

        foreach ($query->with(['testType'])->get() as $labRequest) {
            foreach ($this->modulePricingService->resolveLabBillableLines($labRequest) as $line) {
                $charges[] = $line;
            }
        }

        return $charges;
    }

    private function getPrescriptionCharges($patientId, $branchId = null)
    {
        $query = Prescription::where('billing_status', 'pending')
            ->where('status', 'completed');

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $charges = [];

        foreach ($query->with(['orders.drug'])->get() as $prescription) {
            foreach ($this->modulePricingService->resolvePharmacyBillableLines($prescription) as $line) {
                $charges[] = $line;
            }
        }

        return $charges;
    }

    private function getRadiologyCharges($patientId, $branchId = null)
    {
        $query = RadiologyRequest::where('billing_status', 'pending')
            ->where('status', 'completed');

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereHas('patient', function ($patientQuery) use ($branchId) {
                        $patientQuery->where('branch_id', $branchId);
                    });
            });
        }

        $charges = [];

        foreach ($query->with('modality')->get() as $radiologyRequest) {
            foreach ($this->modulePricingService->resolveRadiologyBillableLines($radiologyRequest) as $line) {
                $charges[] = $line;
            }
        }

        return $charges;
    }

    private function getSurgeryCharges($patientId, $branchId = null): array
    {
        $query = SurgerySchedule::whereIn('status', ['scheduled', 'completed']);

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        if ($branchId) {
            $query->whereHas('theatre', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $charges = [];

        foreach ($query->with(['patient', 'theatre', 'procedure'])->get() as $surgery) {
            if ($this->surgeryHasInvoice($surgery)) {
                continue;
            }

            foreach ($this->modulePricingService->resolveSurgeryBillableLines($surgery) as $line) {
                $line['requires_full_payment'] = false;
                $line['surgery_id'] = $surgery->id;
                $charges[] = $line;
            }
        }

        return $charges;
    }

    private function getWardCharges($patientId, $branchId = null): array
    {
        $query = BedAssignment::where('status', 'active');

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        if ($branchId) {
            $query->whereHas('ward', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $charges = [];

        foreach ($query->with(['patient', 'ward', 'bed'])->get() as $assignment) {
            if ($this->wardAssignmentHasInvoice($assignment)) {
                continue;
            }

            foreach ($this->modulePricingService->resolveWardBillableLines($assignment) as $line) {
                $line['requires_full_payment'] = false;
                $line['assignment_id'] = $assignment->id;
                $charges[] = $line;
            }
        }

        return $charges;
    }

    private function surgeryHasInvoice(SurgerySchedule $surgery): bool
    {
        return Invoice::where('patient_id', $surgery->patient_id)
            ->where('notes', 'LIKE', '%Surgery Schedule #' . $surgery->id . '%')
            ->where('status', '!=', 'cancelled')
            ->exists();
    }

    private function wardAssignmentHasInvoice(BedAssignment $assignment): bool
    {
        return Invoice::where('patient_id', $assignment->patient_id)
            ->where('notes', 'LIKE', '%Bed Assignment #' . $assignment->id . '%')
            ->where('status', '!=', 'cancelled')
            ->exists();
    }

    public function getPendingChargesCount($branchId)
    {
        $count = 0;

        $count += Consultation::whereHas('visit', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })->where('billing_status', '!=', 'billed')
            ->where('consultation_status', 'completed')->count();

        $count += LabRequest::where('branch_id', $branchId)
            ->where('billing_status', '!=', 'billed')
            ->where('status', 'completed')->count();

        $count += Prescription::where('branch_id', $branchId)
            ->where('billing_status', '!=', 'billed')
            ->where('status', 'completed')->count();

        $count += RadiologyRequest::whereHas('patient', function ($query) use ($branchId) {
            $query->whereHas('visits', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        })->where('billing_status', '!=', 'billed')
            ->where('status', 'completed')->count();

        return $count;
    }

    public function getOutstandingAmount($branchId)
    {
        $amount = 0;

        $amount += collect($this->getConsultationCharges(null, $branchId))->sum('amount');
        $amount += collect($this->getLabCharges(null, $branchId))->sum('amount');
        $amount += collect($this->getPrescriptionCharges(null, $branchId))->sum('amount');
        $amount += collect($this->getRadiologyCharges(null, $branchId))->sum('amount');

        $outstandingInvoices = Invoice::where('branch_id', $branchId)
            ->where(function ($query) {
                $query->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
                    ->orWhereIn('status', ['pending', 'partial', 'overdue']);
            })
            ->where('status', '!=', 'cancelled')
            ->where('balance_amount', '>', 0)
            ->get();

        $amount += $outstandingInvoices->sum('balance_amount');

        return round($amount, 2);
    }

    private function getUnpaidInvoiceCharges($patientId, $branchId = null): array
    {
        $query = Invoice::where('patient_id', $patientId)
            ->where('balance_amount', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->whereIn('payment_status', ['unpaid', 'partial', 'overdue']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $charges = [];
        foreach ($query->get() as $invoice) {
            $items = is_array($invoice->items) ? $invoice->items : [];
            $isRegistrationFee = collect($items)->contains(
                fn ($item) => ($item['service_type'] ?? '') === RegistrationFeeService::REGISTRATION_FEE_SERVICE_TYPE
            );

            if (!$isRegistrationFee) {
                continue;
            }

            $firstItem = $items[0] ?? [];
            $charges[] = [
                'id' => $invoice->id,
                'line_id' => 'registration_fee_' . $invoice->id,
                'patient_id' => $invoice->patient_id,
                'type' => 'registration_fee',
                'description' => $firstItem['description'] ?? 'Registration Fee (one-time)',
                'amount' => (float) $invoice->balance_amount,
                'charge_component' => 'module_price',
                'date' => ($invoice->invoice_date ?? $invoice->created_at)->format('Y-m-d H:i:s'),
                'invoice_id' => $invoice->id,
                'status' => 'pending',
                'requires_full_payment' => true,
            ];
        }

        return $charges;
    }

    /**
     * Resolve the billable amount for a single charge parent record (sum of all components).
     */
    public function resolveChargeAmount(string $type, $model): float
    {
        if (!$model) {
            return 0.0;
        }

        if (!empty($model->billing_amount)) {
            return (float) $model->billing_amount;
        }

        return match ($type) {
            'consultation' => $this->modulePricingService->resolveTotalForConsultation($model),
            'lab_test', 'lab' => $this->modulePricingService->resolveTotalForLab($model),
            'prescription' => $this->modulePricingService->resolveTotalForPrescription($model),
            'radiology' => $this->modulePricingService->resolveTotalForRadiology($model),
            'appointment' => $this->modulePricingService->resolveTotalForAppointment($model),
            'surgery' => $this->modulePricingService->resolveTotalForSurgery($model),
            'ward' => $this->modulePricingService->resolveTotalForWard($model),
            default => 0.0,
        };
    }
}
