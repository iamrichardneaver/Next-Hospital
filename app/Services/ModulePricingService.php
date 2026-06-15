<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentFee;
use App\Models\BedAssignment;
use App\Models\Consultation;
use App\Models\Drug;
use App\Models\DrugStock;
use App\Models\ImagingModality;
use App\Models\LabRequest;
use App\Models\LabTest;
use App\Models\LabTestType;
use App\Models\Prescription;
use App\Models\RadiologyRequest;
use App\Models\ServicePricing;
use App\Models\SurgerySchedule;

class ModulePricingService
{
    public const MODULE_CODES = [
        'consultation',
        'lab',
        'pharmacy',
        'radiology',
        'appointment',
        'teleconsultation',
        'surgery',
        'ward',
    ];

    public const APPLIES_ON_VISIT_CHECKIN = 'visit_checkin';
    public const APPLIES_ON_ORDER_CREATED = 'order_created';
    public const APPLIES_ON_APPOINTMENT_BOOKED = 'appointment_booked';
    public const APPLIES_ON_MANUAL = 'manual';

    public function __construct(
        protected PricingService $pricingService
    ) {
    }

    /**
     * Resolve the administrative module fee for a module (additive on top of module prices).
     *
     * @param  string|null  $trigger  Workflow trigger (visit_checkin, order_created, appointment_booked). Manual fees never auto-apply.
     */
    public function resolveModuleFee(
        string $moduleCode,
        int $branchId,
        ?int $patientId = null,
        ?string $trigger = null
    ): float {
        $records = $this->findModuleFeeRecords($moduleCode, $branchId, $trigger);

        if ($records->isEmpty()) {
            return 0.0;
        }

        $total = 0.0;

        foreach ($records as $record) {
            $amount = $patientId
                ? $this->resolvePricedAmount($record->service_id, $branchId, $patientId)
                : (float) $record->base_price;
            $total += $amount;
        }

        return round($total, 2);
    }

    /**
     * Default workflow trigger when applies_on is not set on a module fee record.
     */
    public function defaultAppliesOnForModule(string $moduleCode): ?string
    {
        return match ($moduleCode) {
            'consultation', 'ward' => self::APPLIES_ON_VISIT_CHECKIN,
            'lab', 'pharmacy', 'radiology', 'surgery' => self::APPLIES_ON_ORDER_CREATED,
            'appointment', 'teleconsultation' => self::APPLIES_ON_APPOINTMENT_BOOKED,
            default => null,
        };
    }

    /**
     * Whether a module fee record should fire for the given workflow trigger.
     */
    public function recordMatchesTrigger(ServicePricing $record, string $moduleCode, string $trigger): bool
    {
        $appliesOn = $record->applies_on;

        if ($appliesOn === self::APPLIES_ON_MANUAL) {
            return false;
        }

        if (empty($appliesOn)) {
            $appliesOn = $this->defaultAppliesOnForModule($moduleCode);
        }

        return $appliesOn === $trigger;
    }

    protected function resolvePricedAmount(string $serviceId, int $branchId, int $patientId, array $options = []): float
    {
        try {
            $pricing = $this->pricingService->calculateServicePrice($serviceId, $patientId, $branchId, $options);

            return (float) ($pricing['final_price'] ?? $pricing['patient_co_pay'] ?? $pricing['base_price'] ?? 0);
        } catch (\Exception $e) {
            $price = $this->pricingService->getPrice($serviceId, $branchId, $patientId, $options);

            return $price ?? 0.0;
        }
    }

    protected function enrichChargeLine(array $line, int $branchId, int $patientId, ?string $serviceId = null, array $options = []): array
    {
        if ($serviceId && $this->pricingService->hasConfiguredPrice($serviceId, $branchId)) {
            try {
                $pricing = $this->pricingService->calculateServicePrice($serviceId, $patientId, $branchId, $options);

                return $this->applyPricingBreakdown($line, $pricing);
            } catch (\Exception $e) {
                // fall through to flat amount
            }
        }

        $amount = (float) ($line['amount'] ?? 0);

        return $this->applyPricingBreakdown($line, [
            'base_price' => $amount,
            'calculated_price' => $amount,
            'insurance_coverage' => ['covered_amount' => 0],
            'patient_co_pay' => $amount,
            'final_price' => $amount,
        ]);
    }

    protected function applyPricingBreakdown(array $line, array $pricing): array
    {
        $base = (float) ($pricing['base_price'] ?? 0);
        $calculated = (float) ($pricing['calculated_price'] ?? $base);
        $insurance = (float) ($pricing['insurance_coverage']['covered_amount'] ?? 0);
        $copay = (float) ($pricing['patient_co_pay'] ?? $pricing['final_price'] ?? max(0, $calculated - $insurance));
        $final = (float) ($pricing['final_price'] ?? $copay);
        $discount = max(0, round($base - $calculated, 2));

        $line['base_amount'] = round($base, 2);
        $line['discount_amount'] = $discount;
        $line['insurance_coverage'] = round($insurance, 2);
        $line['patient_copay'] = round($copay, 2);
        $line['final_amount'] = round($final, 2);
        $line['amount'] = round($final, 2);

        return $line;
    }

    protected function appendModuleFeeLine(
        array &$lines,
        string $moduleCode,
        int $branchId,
        int $patientId,
        string $trigger,
        string $type,
        int $parentId,
        ?string $date,
        array $extra = []
    ): void {
        $records = $this->findModuleFeeRecords($moduleCode, $branchId, $trigger);
        foreach ($records as $feeRecord) {
            $amount = $this->resolvePricedAmount($feeRecord->service_id, $branchId, $patientId);
            if ($amount <= 0) {
                continue;
            }

            $line = $this->buildChargeLine(
                type: $type,
                description: $feeRecord->service_name ?? ucfirst($moduleCode) . ' Service Fee',
                amount: $amount,
                chargeComponent: 'admin_fee',
                parentId: $parentId,
                patientId: $patientId,
                date: $date,
                extra: array_merge($extra, ['service_id' => $feeRecord->service_id])
            );

            $lines[] = $this->enrichChargeLine($line, $branchId, $patientId, $feeRecord->service_id);
        }
    }

    /**
     * Build billable charge lines for a lab request (test price + optional lab module fee).
     */
    public function resolveLabBillableLines(LabRequest $labRequest): array
    {
        $branchId = (int) $labRequest->branch_id;
        $patientId = (int) $labRequest->patient_id;
        $modulePrice = $this->getLabModulePrice($labRequest);
        $testName = $labRequest->test_type ?? 'Laboratory Test';
        $lines = [];

        $serviceId = $labRequest->test_type_id ? "lab_test_{$labRequest->test_type_id}" : null;
        $date = $labRequest->created_at?->format('Y-m-d H:i:s');

        if ($modulePrice > 0) {
            $line = $this->buildChargeLine(
                type: 'lab_test',
                description: 'Lab Test - ' . $testName,
                amount: $modulePrice,
                chargeComponent: 'module_price',
                parentId: $labRequest->id,
                patientId: $patientId,
                date: $date,
                extra: [
                    'visit_id' => $labRequest->consultation_id,
                    'service_id' => $serviceId,
                ]
            );
            $lines[] = $this->enrichChargeLine(
                $line,
                $branchId,
                $patientId,
                $serviceId,
                ['priority' => $labRequest->priority ?? 'routine']
            );
        }

        $this->appendModuleFeeLine(
            $lines,
            'lab',
            $branchId,
            $patientId,
            self::APPLIES_ON_ORDER_CREATED,
            'lab_test',
            $labRequest->id,
            $date,
            ['visit_id' => $labRequest->consultation_id]
        );

        return $lines;
    }

    /**
     * Build billable charge lines for a radiology request (study price + optional radiology module fee).
     */
    public function resolveRadiologyBillableLines(RadiologyRequest $radiologyRequest): array
    {
        $branchId = (int) ($radiologyRequest->branch_id ?? $radiologyRequest->patient?->branch_id);
        $patientId = (int) $radiologyRequest->patient_id;
        $modulePrice = $this->getRadiologyModulePrice($radiologyRequest);
        $studyName = $radiologyRequest->study_type ?? 'Radiology Study';
        $lines = [];

        $serviceId = $radiologyRequest->modality_id ? "radiology_{$radiologyRequest->modality_id}" : null;
        $date = $radiologyRequest->created_at?->format('Y-m-d H:i:s');

        if ($modulePrice > 0) {
            $line = $this->buildChargeLine(
                type: 'radiology',
                description: 'Radiology - ' . $studyName,
                amount: $modulePrice,
                chargeComponent: 'module_price',
                parentId: $radiologyRequest->id,
                patientId: $patientId,
                date: $date,
                extra: [
                    'consultation_id' => $radiologyRequest->consultation_id,
                    'service_id' => $serviceId,
                ]
            );
            $lines[] = $this->enrichChargeLine($line, $branchId, $patientId, $serviceId);
        }

        $this->appendModuleFeeLine(
            $lines,
            'radiology',
            $branchId,
            $patientId,
            self::APPLIES_ON_ORDER_CREATED,
            'radiology',
            $radiologyRequest->id,
            $date,
            ['consultation_id' => $radiologyRequest->consultation_id]
        );

        return $lines;
    }

    /**
     * Build billable charge lines for a prescription (drug totals + optional pharmacy module fee).
     */
    public function resolvePharmacyBillableLines(Prescription $prescription): array
    {
        $branchId = (int) $prescription->branch_id;
        $patientId = (int) $prescription->patient_id;
        $prescription->loadMissing('orders.drug');
        $lines = [];
        $drugTotal = 0.0;
        $drugDescriptions = [];

        foreach ($prescription->orders as $order) {
            $lineAmount = $this->getDrugLinePrice($order, $patientId, $branchId);
            if ($lineAmount > 0) {
                $drugTotal += $lineAmount;
                $drugName = $order->drug?->name ?? 'Medication';
                $drugDescriptions[] = $drugName . ' (x' . $order->quantity . ')';
            }
        }

        $date = $prescription->created_at?->format('Y-m-d H:i:s');

        if ($drugTotal > 0) {
            $description = count($drugDescriptions) === 1
                ? 'Prescription - ' . $drugDescriptions[0]
                : 'Prescription - ' . $prescription->orders->count() . ' items';

            $line = $this->buildChargeLine(
                type: 'prescription',
                description: $description,
                amount: round($drugTotal, 2),
                chargeComponent: 'module_price',
                parentId: $prescription->id,
                patientId: $patientId,
                date: $date,
                extra: ['consultation_id' => $prescription->consultation_id]
            );
            $lines[] = $this->enrichChargeLine($line, $branchId, $patientId);
        }

        $this->appendModuleFeeLine(
            $lines,
            'pharmacy',
            $branchId,
            $patientId,
            self::APPLIES_ON_ORDER_CREATED,
            'prescription',
            $prescription->id,
            $date,
            ['consultation_id' => $prescription->consultation_id]
        );

        return $lines;
    }

    /**
     * Build billable charge lines for a consultation (module fee / standalone — no separate module catalog price).
     */
    public function resolveConsultationBillableLines(Consultation $consultation): array
    {
        $branchId = (int) ($consultation->branch_id ?? $consultation->visit?->branch_id);
        $patientId = (int) $consultation->patient_id;

        if ($this->shouldSkipConsultationFeeDueToAppointment($consultation, $branchId)) {
            return [];
        }

        $feeRecords = $this->findModuleFeeRecords('consultation', $branchId, self::APPLIES_ON_VISIT_CHECKIN);
        $feeRecord = $feeRecords->first();
        $standaloneRecord = $this->pricingService->findServicePricing('consultation', $branchId);

        $doctorName = $consultation->doctor?->name ?? 'Unknown Doctor';
        $lines = [];

        if ($feeRecord) {
            $amount = $this->resolvePricedAmount($feeRecord->service_id, $branchId, $patientId, [
                'doctor_id' => $consultation->doctor_id,
            ]);
            if ($amount > 0) {
                $line = $this->buildChargeLine(
                    type: 'consultation',
                    description: $feeRecord->service_name ?? 'Consultation Service Fee',
                    amount: $amount,
                    chargeComponent: 'admin_fee',
                    parentId: $consultation->id,
                    patientId: $patientId,
                    date: $consultation->created_at?->format('Y-m-d H:i:s'),
                    extra: [
                        'consultation_id' => $consultation->id,
                        'service_id' => $feeRecord->service_id,
                    ]
                );
                $lines[] = $this->enrichChargeLine($line, $branchId, $patientId, $feeRecord->service_id, [
                    'doctor_id' => $consultation->doctor_id,
                ]);
            }
        } elseif ($standaloneRecord && $standaloneRecord->pricing_type !== 'module_fee') {
            $amount = $this->resolvePricedAmount('consultation', $branchId, $patientId, [
                'doctor_id' => $consultation->doctor_id,
            ]);
            if ($amount > 0) {
                $line = $this->buildChargeLine(
                    type: 'consultation',
                    description: 'Medical Consultation - Dr. ' . $doctorName,
                    amount: $amount,
                    chargeComponent: 'module_price',
                    parentId: $consultation->id,
                    patientId: $patientId,
                    date: $consultation->created_at?->format('Y-m-d H:i:s'),
                    extra: [
                        'consultation_id' => $consultation->id,
                        'service_id' => 'consultation',
                    ]
                );
                $lines[] = $this->enrichChargeLine($line, $branchId, $patientId, 'consultation', [
                    'doctor_id' => $consultation->doctor_id,
                ]);
            }
        }

        return $lines;
    }

    /**
     * Skip visit_checkin consultation fee when appointment_booked fee already covers the same visit.
     */
    protected function shouldSkipConsultationFeeDueToAppointment(Consultation $consultation, int $branchId): bool
    {
        $hasAppointmentBookedFee = $this->resolveModuleFee('appointment', $branchId, (int) $consultation->patient_id, self::APPLIES_ON_APPOINTMENT_BOOKED) > 0
            || $this->resolveModuleFee('teleconsultation', $branchId, (int) $consultation->patient_id, self::APPLIES_ON_APPOINTMENT_BOOKED) > 0;

        if (!$hasAppointmentBookedFee) {
            return false;
        }

        $consultDate = $consultation->consultation_date ?? $consultation->created_at?->toDateString();

        return Appointment::where('patient_id', $consultation->patient_id)
            ->where('billing_status', 'pending')
            ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
            ->when($consultation->doctor_id, fn ($q) => $q->where('doctor_id', $consultation->doctor_id))
            ->when($consultDate, fn ($q) => $q->whereDate('appointment_date', $consultDate))
            ->exists();
    }

    /**
     * Build billable charge lines for an appointment (native fee + optional appointment module fee).
     * Appointment module fee is separate from consultation module fee.
     */
    public function resolveAppointmentBillableLines(Appointment $appointment): array
    {
        $branchId = (int) $appointment->branch_id;
        $patientId = (int) $appointment->patient_id;
        $nativeFee = $this->getAppointmentNativeFee($appointment);
        $lines = [];

        $date = $appointment->created_at?->format('Y-m-d H:i:s');

        if ($nativeFee > 0) {
            $doctorName = $appointment->doctor?->name ?? 'Unknown Doctor';
            $appointmentTypeLabel = ucfirst(str_replace('-', ' ', $appointment->appointment_type));

            $line = $this->buildChargeLine(
                type: 'appointment',
                description: $appointmentTypeLabel . ' Appointment - Dr. ' . $doctorName,
                amount: $nativeFee,
                chargeComponent: 'module_price',
                parentId: $appointment->id,
                patientId: $patientId,
                date: $date,
                extra: ['appointment_id' => $appointment->id]
            );
            $lines[] = $this->enrichChargeLine($line, $branchId, $patientId);
        }

        $moduleCode = $appointment->appointment_type === 'teleconsultation'
            ? 'teleconsultation'
            : 'appointment';

        $this->appendModuleFeeLine(
            $lines,
            $moduleCode,
            $branchId,
            $patientId,
            self::APPLIES_ON_APPOINTMENT_BOOKED,
            'appointment',
            $appointment->id,
            $date,
            ['appointment_id' => $appointment->id]
        );

        return $lines;
    }

    /**
     * Lab test price from module sources (test type / individual test), with priority multiplier.
     * Item-override service pricing replaces the test price when configured (backward compat).
     */
    public function getLabModulePrice(LabRequest $labRequest): float
    {
        $branchId = (int) $labRequest->branch_id;
        $patientId = (int) $labRequest->patient_id;
        $priority = $labRequest->priority ?? 'routine';
        $basePrice = 0.0;

        if ($labRequest->test_type_id) {
            $individualTest = LabTest::where('test_type_id', $labRequest->test_type_id)
                ->where('is_active', true)
                ->first();

            if ($individualTest && $individualTest->cost !== null) {
                $basePrice = (float) $individualTest->cost;
            } else {
                $testType = LabTestType::find($labRequest->test_type_id);
                if ($testType && $testType->cost !== null) {
                    $basePrice = (float) $testType->cost;
                }
            }

            $overridePrice = $this->getItemOverridePrice("lab_test_{$labRequest->test_type_id}", $branchId, $patientId);
            if ($overridePrice !== null) {
                return $overridePrice;
            }
        }

        if ($basePrice <= 0 && $labRequest->test_id) {
            $individualTest = LabTest::find($labRequest->test_id);
            if ($individualTest && $individualTest->cost !== null && $individualTest->cost > 0) {
                $basePrice = (float) $individualTest->cost;
            }
        }

        if ($basePrice <= 0 && $labRequest->test_type) {
            $legacyPricing = ServicePricing::where('service_type', 'lab_test')
                ->where('service_id', $labRequest->test_type)
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();

            if ($legacyPricing) {
                $basePrice = (float) $legacyPricing->base_price;
            }
        }

        if ($basePrice <= 0) {
            return 0.0;
        }

        return round($basePrice * $this->getPriorityMultiplier($priority), 2);
    }

    /**
     * Radiology study price from modality / item-override (ignores billing_amount snapshot).
     */
    public function getRadiologyModulePrice(RadiologyRequest $radiologyRequest): float
    {
        $radiologyRequest->loadMissing('modality');
        $branchId = (int) ($radiologyRequest->branch_id ?? $radiologyRequest->patient?->branch_id);
        $patientId = (int) $radiologyRequest->patient_id;

        if ($radiologyRequest->modality_id) {
            $overridePrice = $this->getItemOverridePrice("radiology_{$radiologyRequest->modality_id}", $branchId, $patientId);
            if ($overridePrice !== null) {
                return $overridePrice;
            }

            $imgOverride = $this->getItemOverridePrice("IMG-{$radiologyRequest->modality_id}", $branchId, $patientId);
            if ($imgOverride !== null) {
                return $imgOverride;
            }

            $servicePrice = $this->pricingService->getPrice(
                "radiology_{$radiologyRequest->modality_id}",
                $branchId,
                $patientId
            );
            if ($servicePrice !== null && $servicePrice > 0) {
                return $servicePrice;
            }

            $imagingPrice = $this->pricingService->getPrice(
                "IMG-{$radiologyRequest->modality_id}",
                $branchId,
                $patientId
            );
            if ($imagingPrice !== null && $imagingPrice > 0) {
                return $imagingPrice;
            }
        }

        $serviceId = (string) ($radiologyRequest->modality_id ?? $radiologyRequest->modality?->code ?? '');
        if ($serviceId !== '') {
            $pricing = ServicePricing::where('service_type', 'radiology')
                ->where('service_id', $serviceId)
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();

            if ($pricing && $pricing->pricing_type !== 'module_fee') {
                return (float) $pricing->base_price;
            }
        }

        if ($radiologyRequest->modality?->base_cost) {
            return (float) $radiologyRequest->modality->base_cost;
        }

        return 0.0;
    }

    /**
     * Sum total billable amount for a parent record (all line components).
     */
    public function resolveTotalForLab(LabRequest $labRequest): float
    {
        return collect($this->resolveLabBillableLines($labRequest))->sum('amount');
    }

    public function resolveTotalForRadiology(RadiologyRequest $radiologyRequest): float
    {
        return collect($this->resolveRadiologyBillableLines($radiologyRequest))->sum('amount');
    }

    public function resolveTotalForPrescription(Prescription $prescription): float
    {
        return collect($this->resolvePharmacyBillableLines($prescription))->sum('amount');
    }

    public function resolveTotalForConsultation(Consultation $consultation): float
    {
        return collect($this->resolveConsultationBillableLines($consultation))->sum('amount');
    }

    public function resolveTotalForAppointment(Appointment $appointment): float
    {
        return collect($this->resolveAppointmentBillableLines($appointment))->sum('amount');
    }

    protected function getConsultationCharge(Consultation $consultation): float
    {
        return collect($this->resolveConsultationBillableLines($consultation))->sum('amount');
    }

    protected function getDrugLinePrice($order, int $patientId, int $branchId): float
    {
        if ($order->drug_id) {
            try {
                $pricing = $this->pricingService->calculateDrugPrice(
                    $order->drug_id,
                    $order->quantity,
                    $patientId,
                    $branchId
                );

                return (float) ($pricing['final_price'] ?? ($order->quantity * $order->unit_price));
            } catch (\Exception $e) {
                return (float) ($order->quantity * $order->unit_price);
            }
        }

        return (float) ($order->quantity * $order->unit_price);
    }

    protected function getAppointmentNativeFee(Appointment $appointment): float
    {
        if ($appointment->slot && $appointment->slot->fee) {
            return (float) $appointment->slot->fee;
        }

        $appointmentFee = AppointmentFee::where('branch_id', $appointment->branch_id)
            ->where('appointment_type', $appointment->appointment_type)
            ->where(function ($q) use ($appointment) {
                $q->where('doctor_id', $appointment->doctor_id)
                    ->orWhereNull('doctor_id');
            })
            ->where('is_active', true)
            ->effective()
            ->orderBy('doctor_id', 'desc')
            ->first();

        if ($appointmentFee) {
            return (float) $appointmentFee->calculateTotalFee();
        }

        return 0.0;
    }

    protected function getItemOverridePrice(string $serviceId, int $branchId, ?int $patientId): ?float
    {
        $record = $this->pricingService->findServicePricing($serviceId, $branchId);

        if (!$record || !$record->is_active) {
            return null;
        }

        if ($record->pricing_type === 'module_fee') {
            return null;
        }

        if ($record->pricing_type === 'item_override' || $record->is_additive === false) {
            $price = $this->pricingService->getPrice($serviceId, $branchId, $patientId);

            return $price !== null ? (float) $price : (float) $record->base_price;
        }

        return null;
    }

    public function findModuleFeeRecords(string $moduleCode, int $branchId, ?string $trigger = null)
    {
        $records = ServicePricing::where('branch_id', $branchId)
            ->where('is_active', true)
            ->where('pricing_type', 'module_fee')
            ->where(function ($query) use ($moduleCode) {
                $query->whereJsonContains('module_codes', $moduleCode);
            })
            ->get();

        if ($trigger === null) {
            return $records;
        }

        return $records->filter(
            fn (ServicePricing $record) => $this->recordMatchesTrigger($record, $moduleCode, $trigger)
        )->values();
    }

    protected function buildChargeLine(
        string $type,
        string $description,
        float $amount,
        string $chargeComponent,
        int $parentId,
        int $patientId,
        ?string $date,
        array $extra = []
    ): array {
        $lineId = $type . '_' . $parentId . '_' . $chargeComponent;

        return array_merge([
            'id' => $parentId,
            'line_id' => $lineId,
            'patient_id' => $patientId,
            'type' => $type,
            'description' => $description,
            'amount' => round($amount, 2),
            'charge_component' => $chargeComponent,
            'date' => $date ?? now()->format('Y-m-d H:i:s'),
            'status' => 'pending',
        ], $extra);
    }

    protected function getPriorityMultiplier(string $priority): float
    {
        return match ($priority) {
            'stat' => 2.0,
            'urgent' => 1.5,
            'routine' => 1.0,
            default => 1.0,
        };
    }

    /**
     * Billable lines for a consultation intervention (lab, imaging, medication).
     */
    public function resolveInterventionBillableLines(
        $intervention,
        Consultation $consultation,
        int $branchId
    ): array {
        $patientId = (int) $consultation->patient_id;
        $lines = [];

        if ($intervention->intervention_type === 'lab_order' && $intervention->lab_test_id) {
            $testType = LabTestType::find($intervention->lab_test_id);
            $testName = $testType?->test_name ?? $intervention->description ?? 'Lab Test';
            $priority = $intervention->priority ?? 'routine';
            $basePrice = 0.0;

            if ($testType && $testType->cost !== null) {
                $basePrice = (float) $testType->cost;
            }

            $overridePrice = $this->getItemOverridePrice("lab_test_{$intervention->lab_test_id}", $branchId, $patientId);
            $modulePrice = $overridePrice ?? round($basePrice * $this->getPriorityMultiplier($priority), 2);

            if ($modulePrice > 0) {
                $lines[] = $this->buildChargeLine(
                    type: 'lab_test',
                    description: 'Lab Test - ' . $testName,
                    amount: $modulePrice,
                    chargeComponent: 'module_price',
                    parentId: $intervention->id,
                    patientId: $patientId,
                    date: now()->format('Y-m-d H:i:s'),
                    extra: [
                        'intervention_id' => $intervention->id,
                        'service_id' => "lab_test_{$intervention->lab_test_id}",
                    ]
                );
            }

            $this->appendModuleFeeLine(
                $lines,
                'lab',
                $branchId,
                $patientId,
                self::APPLIES_ON_ORDER_CREATED,
                'lab_test',
                $intervention->id,
                now()->format('Y-m-d H:i:s'),
                ['intervention_id' => $intervention->id]
            );

            return $lines;
        }

        if ($intervention->intervention_type === 'imaging_order' && $intervention->imaging_id) {
            $modality = ImagingModality::find($intervention->imaging_id);
            $studyName = $modality?->name ?? $intervention->description ?? 'Radiology Study';
            $modulePrice = 0.0;

            $overridePrice = $this->getItemOverridePrice("radiology_{$intervention->imaging_id}", $branchId, $patientId);
            if ($overridePrice !== null) {
                $modulePrice = $overridePrice;
            } elseif ($modality?->base_cost) {
                $modulePrice = (float) $modality->base_cost;
            }

            if ($modulePrice > 0) {
                $lines[] = $this->buildChargeLine(
                    type: 'radiology',
                    description: 'Radiology - ' . $studyName,
                    amount: $modulePrice,
                    chargeComponent: 'module_price',
                    parentId: $intervention->id,
                    patientId: $patientId,
                    date: now()->format('Y-m-d H:i:s'),
                    extra: ['intervention_id' => $intervention->id]
                );
            }

            $this->appendModuleFeeLine(
                $lines,
                'radiology',
                $branchId,
                $patientId,
                self::APPLIES_ON_ORDER_CREATED,
                'radiology',
                $intervention->id,
                now()->format('Y-m-d H:i:s'),
                ['intervention_id' => $intervention->id]
            );

            return $lines;
        }

        if ($intervention->intervention_type === 'medication' && $intervention->medication_id) {
            try {
                $pricing = $this->pricingService->calculateDrugPrice(
                    $intervention->medication_id,
                    1,
                    $patientId,
                    $branchId
                );
                $amount = (float) ($pricing['final_price'] ?? 0);
                if ($amount > 0) {
                    $drug = Drug::find($intervention->medication_id);
                    $lines[] = $this->buildChargeLine(
                        type: 'prescription',
                        description: 'Medication - ' . ($drug?->name ?? $intervention->description),
                        amount: $amount,
                        chargeComponent: 'module_price',
                        parentId: $intervention->id,
                        patientId: $patientId,
                        date: now()->format('Y-m-d H:i:s'),
                        extra: ['intervention_id' => $intervention->id]
                    );
                }
            } catch (\Exception $e) {
                // No stock — skip item price
            }

            $this->appendModuleFeeLine(
                $lines,
                'pharmacy',
                $branchId,
                $patientId,
                self::APPLIES_ON_ORDER_CREATED,
                'prescription',
                $intervention->id,
                now()->format('Y-m-d H:i:s'),
                ['intervention_id' => $intervention->id]
            );

            return $lines;
        }

        return $lines;
    }

    /**
     * Build billable charge lines for a surgery schedule (procedure price + optional surgery module fee).
     */
    public function resolveSurgeryBillableLines(SurgerySchedule $surgery): array
    {
        $surgery->loadMissing(['patient', 'theatre', 'procedure']);
        $branchId = (int) ($surgery->theatre?->branch_id ?? $surgery->patient?->branch_id ?? 0);
        if ($branchId <= 0) {
            return [];
        }

        $patientId = (int) $surgery->patient_id;
        $lines = [];
        $date = ($surgery->surgery_date ?? $surgery->created_at)?->format('Y-m-d H:i:s');

        $serviceId = $surgery->procedure_id
            ? 'PROC_' . $surgery->procedure_id
            : 'SURGERY_' . strtoupper($surgery->surgery_type ?? 'GENERAL');

        if ($this->pricingService->hasConfiguredPrice($serviceId, $branchId)) {
            $amount = $this->resolvePricedAmount($serviceId, $branchId, $patientId, [
                'surgery_type' => $surgery->surgery_type,
                'priority' => $surgery->priority,
            ]);
            if ($amount > 0) {
                $line = $this->buildChargeLine(
                    type: 'surgery',
                    description: 'Surgical Procedure - ' . ($surgery->procedure?->name ?? $surgery->surgery_type ?? 'Surgery'),
                    amount: $amount,
                    chargeComponent: 'module_price',
                    parentId: $surgery->id,
                    patientId: $patientId,
                    date: $date,
                    extra: ['surgery_id' => $surgery->id, 'service_id' => $serviceId]
                );
                $lines[] = $this->enrichChargeLine($line, $branchId, $patientId, $serviceId);
            }
        }

        $this->appendModuleFeeLine(
            $lines,
            'surgery',
            $branchId,
            $patientId,
            self::APPLIES_ON_ORDER_CREATED,
            'surgery',
            $surgery->id,
            $date,
            ['surgery_id' => $surgery->id]
        );

        return $lines;
    }

    /**
     * Build billable charge lines for an active ward admission (bed rate + optional ward module fee).
     */
    public function resolveWardBillableLines(BedAssignment $assignment): array
    {
        $assignment->loadMissing(['patient', 'ward', 'bed']);
        $branchId = (int) ($assignment->bed?->branch_id ?? $assignment->ward?->branch_id ?? $assignment->patient?->branch_id ?? 0);
        if ($branchId <= 0 || !$assignment->ward) {
            return [];
        }

        $patientId = (int) $assignment->patient_id;
        $serviceId = 'BED_' . strtoupper($assignment->ward->type);
        $lines = [];
        $date = ($assignment->admission_date ?? $assignment->created_at)?->format('Y-m-d H:i:s');

        if ($this->pricingService->hasConfiguredPrice($serviceId, $branchId)) {
            $wardBilling = app(WardBillingService::class);
            $tempAssignment = clone $assignment;
            if ($assignment->status === 'active' && !$assignment->discharge_date) {
                $tempAssignment->discharge_date = now();
            }
            $bedCharges = $wardBilling->calculateBedCharges($tempAssignment);
            $amount = (float) ($bedCharges['total_charge'] ?? 0);

            if ($amount > 0) {
                $line = $this->buildChargeLine(
                    type: 'ward',
                    description: 'Ward Accommodation - ' . ($bedCharges['ward_name'] ?? $assignment->ward->name)
                        . ' (' . ($bedCharges['days'] ?? 1) . ' days)',
                    amount: $amount,
                    chargeComponent: 'module_price',
                    parentId: $assignment->id,
                    patientId: $patientId,
                    date: $date,
                    extra: [
                        'assignment_id' => $assignment->id,
                        'service_id' => $serviceId,
                        'ward_id' => $assignment->ward_id,
                    ]
                );
                $lines[] = $this->enrichChargeLine($line, $branchId, $patientId, $serviceId, [
                    'ward_type' => $assignment->ward->type,
                ]);
            }
        }

        $this->appendModuleFeeLine(
            $lines,
            'ward',
            $branchId,
            $patientId,
            self::APPLIES_ON_VISIT_CHECKIN,
            'ward',
            $assignment->id,
            $date,
            ['assignment_id' => $assignment->id]
        );

        return $lines;
    }

    public function resolveTotalForSurgery(SurgerySchedule $surgery): float
    {
        return collect($this->resolveSurgeryBillableLines($surgery))->sum('amount');
    }

    public function resolveTotalForWard(BedAssignment $assignment): float
    {
        return collect($this->resolveWardBillableLines($assignment))->sum('amount');
    }

    /**
     * Appointment cost estimate with additive module fees (for API / mobile).
     */
    public function buildAppointmentCostData(
        int $branchId,
        string $appointmentType,
        float $nativeFee,
        ?int $patientId = null,
        string $currency = 'GHS',
        string $source = 'slot'
    ): array {
        $moduleCode = $appointmentType === 'teleconsultation' ? 'teleconsultation' : 'appointment';
        $moduleFee = $this->resolveModuleFee($moduleCode, $branchId, $patientId, self::APPLIES_ON_APPOINTMENT_BOOKED);
        $feeRecords = $this->findModuleFeeRecords($moduleCode, $branchId, self::APPLIES_ON_APPOINTMENT_BOOKED);

        $chargeLines = [];
        if ($nativeFee > 0) {
            $chargeLines[] = [
                'description' => 'Appointment Fee',
                'amount' => round($nativeFee, 2),
                'base_amount' => round($nativeFee, 2),
                'discount_amount' => 0,
                'insurance_coverage' => 0,
                'patient_copay' => round($nativeFee, 2),
                'final_amount' => round($nativeFee, 2),
                'charge_component' => 'module_price',
                'type' => 'appointment',
            ];
        }

        foreach ($feeRecords as $feeRecord) {
            $feeAmount = $patientId
                ? $this->resolvePricedAmount($feeRecord->service_id, $branchId, $patientId)
                : (float) $feeRecord->base_price;
            if ($feeAmount <= 0) {
                continue;
            }
            $feeLine = [
                'description' => $feeRecord->service_name ?? 'Appointment Service Fee',
                'amount' => round($feeAmount, 2),
                'charge_component' => 'admin_fee',
                'type' => 'appointment',
                'service_id' => $feeRecord->service_id,
            ];
            if ($patientId) {
                $feeLine = $this->enrichChargeLine($feeLine, $branchId, $patientId, $feeRecord->service_id);
            } else {
                $feeLine = $this->applyPricingBreakdown($feeLine, [
                    'base_price' => $feeAmount,
                    'calculated_price' => $feeAmount,
                    'insurance_coverage' => ['covered_amount' => 0],
                    'patient_co_pay' => $feeAmount,
                    'final_price' => $feeAmount,
                ]);
            }
            $chargeLines[] = $feeLine;
        }

        $totalCost = round(collect($chargeLines)->sum(function (array $line) {
            return (float) ($line['patient_copay'] ?? $line['final_amount'] ?? $line['amount'] ?? 0);
        }), 2);
        $moduleFee = round($moduleFee, 2);

        return [
            'base_fee' => round($nativeFee, 2),
            'module_fee' => round($moduleFee, 2),
            'appointment_type' => $appointmentType,
            'total_cost' => $totalCost,
            'currency' => $currency,
            'source' => $source,
            'charge_lines' => $chargeLines,
            'breakdown' => [
                'consultation_fee' => round($nativeFee, 2),
                'module_fee' => round($moduleFee, 2),
                'platform_fee' => 0.0,
                'tax' => 0.0,
            ],
        ];
    }

    /**
     * Convert internal charge line to invoice item shape.
     */
    public function chargeLineToInvoiceItem(array $line, array $extra = []): array
    {
        return array_merge([
            'id' => $line['line_id'] ?? ('item_' . uniqid()),
            'description' => $line['description'],
            'quantity' => 1,
            'unit_price' => $line['amount'],
            'total' => $line['amount'],
            'service_type' => $line['type'],
            'charge_component' => $line['charge_component'] ?? null,
            'parent_id' => $line['id'] ?? null,
        ], $extra);
    }

    /**
     * Normalize charge lines for API responses.
     */
    public function formatChargeLinesForApi(array $lines): array
    {
        return array_map(function (array $line) {
            $formatted = [
                'id' => $line['id'] ?? null,
                'line_id' => $line['line_id'] ?? null,
                'type' => $line['type'] ?? null,
                'description' => $line['description'] ?? '',
                'amount' => (float) ($line['amount'] ?? $line['final_amount'] ?? 0),
                'charge_component' => $line['charge_component'] ?? null,
                'date' => $line['date'] ?? null,
                'service_id' => $line['service_id'] ?? null,
            ];

            foreach (['base_amount', 'discount_amount', 'insurance_coverage', 'patient_copay', 'final_amount'] as $field) {
                if (isset($line[$field])) {
                    $formatted[$field] = (float) $line[$field];
                }
            }

            return $formatted;
        }, $lines);
    }
}
