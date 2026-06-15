<?php

namespace App\Exceptions;

use Exception;

class PaymentGateException extends Exception
{
    protected float $amountDue;
    protected int $patientId;
    protected ?int $visitId;

    public function __construct(
        string $message = 'Full payment required before service',
        float $amountDue = 0,
        int $patientId = 0,
        ?int $visitId = null
    ) {
        parent::__construct($message);
        $this->amountDue = $amountDue;
        $this->patientId = $patientId;
        $this->visitId = $visitId;
    }

    public function getAmountDue(): float
    {
        return $this->amountDue;
    }

    public function getPatientId(): int
    {
        return $this->patientId;
    }

    public function getVisitId(): ?int
    {
        return $this->visitId;
    }

    public function toArray(): array
    {
        return [
            'payment_required' => true,
            'can_proceed' => false,
            'message' => $this->getMessage(),
            'amount_due' => round($this->amountDue, 2),
            'patient_id' => $this->patientId,
            'visit_id' => $this->visitId,
            'cashier_url' => url('/cashier'),
        ];
    }
}
