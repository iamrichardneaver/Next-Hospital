<?php

namespace App\Services;

use App\Models\Notification;

class AppNotificationService
{
    public function notifyUser(
        int $recipientId,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $priority = 'medium',
        ?int $senderId = null
    ): Notification {
        $payload = array_merge($data, [
            'type' => $type,
            'screen' => $data['screen'] ?? $this->screenForType($type),
        ]);

        if (isset($payload['id'])) {
            $payload['id'] = (string) $payload['id'];
        }

        return Notification::create([
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'data' => $payload,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function notifyAppointmentScheduled($appointment, ?int $senderId = null): void
    {
        $appointment->loadMissing(['patient', 'doctor']);

        $date = optional($appointment->appointment_date)->format('M j, Y');
        $time = $appointment->appointment_time
            ? \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A')
            : null;
        $when = trim(collect([$date, $time])->filter()->implode(' at '));
        $doctorName = $appointment->doctor?->name ?? 'your doctor';
        $patientName = $appointment->patient?->full_name ?? 'a patient';

        $patientUserId = $appointment->patient?->user_id;
        if ($patientUserId) {
            $this->notifyUser(
                (int) $patientUserId,
                'appointment',
                'Appointment Confirmed',
                "Your appointment with Dr. {$doctorName} is scheduled for {$when}.",
                [
                    'screen' => 'Schedule',
                    'id' => (string) $appointment->id,
                    'appointment_id' => (string) $appointment->id,
                ],
                'high',
                $senderId
            );
        }

        if ($appointment->doctor_id) {
            $this->notifyUser(
                (int) $appointment->doctor_id,
                'appointment',
                'New Appointment Scheduled',
                "You have an appointment with {$patientName} on {$when}.",
                [
                    'screen' => 'DoctorSchedule',
                    'id' => (string) $appointment->id,
                    'appointment_id' => (string) $appointment->id,
                ],
                'high',
                $senderId
            );
        }
    }

    public function notifyPaymentReceived($payment, ?int $senderId = null): void
    {
        $payment->loadMissing(['patient']);

        $patientUserId = $payment->patient?->user_id;
        if (!$patientUserId) {
            return;
        }

        $amount = number_format((float) $payment->amount, 2);

        $this->notifyUser(
            (int) $patientUserId,
            'payment',
            'Payment Received',
            "We received your payment of GHS {$amount}. Thank you.",
            [
                'screen' => 'PaymentHistory',
                'id' => (string) $payment->id,
                'payment_id' => (string) $payment->id,
            ],
            'medium',
            $senderId
        );
    }

    public function notifyExpenseDecision($expense, string $decision, ?int $senderId = null): void
    {
        $expense->loadMissing(['creator', 'category']);

        if (!$expense->created_by) {
            return;
        }

        $reference = $expense->expense_reference ?? ('#' . $expense->id);
        $approved = $decision === 'approved';

        $this->notifyUser(
            (int) $expense->created_by,
            'expense',
            $approved ? 'Expense Approved' : 'Expense Rejected',
            $approved
                ? "Your expense {$reference} has been approved."
                : "Your expense {$reference} was rejected.",
            [
                'screen' => 'Expenses',
                'id' => (string) $expense->id,
                'expense_id' => (string) $expense->id,
                'decision' => $decision,
            ],
            'high',
            $senderId
        );
    }

    protected function screenForType(string $type): string
    {
        return match ($type) {
            'appointment' => 'Schedule',
            'lab_result', 'lab_result_ready' => 'LabTests',
            'payment' => 'PaymentHistory',
            'expense' => 'Expenses',
            'prescription' => 'Medications',
            'config_update' => 'Dashboard',
            default => 'Notifications',
        };
    }
}
