<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorReview;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DoctorReviewService
{
    public function averageForDoctor(int $doctorId): ?float
    {
        $average = DoctorReview::where('doctor_id', $doctorId)->avg('rating');

        return $average !== null ? round((float) $average, 2) : null;
    }

    public function reviewCountForDoctor(int $doctorId): int
    {
        return DoctorReview::where('doctor_id', $doctorId)->count();
    }

    public function ratingStatsForDoctor(int $doctorId): array
    {
        return [
            'rating' => $this->averageForDoctor($doctorId),
            'review_count' => $this->reviewCountForDoctor($doctorId),
        ];
    }

    public function listForDoctor(int $doctorId, int $perPage = 20): LengthAwarePaginator
    {
        return DoctorReview::with(['patient:id,first_name,last_name', 'appointment:id,appointment_number,appointment_date'])
            ->where('doctor_id', $doctorId)
            ->latest('id')
            ->paginate($perPage);
    }

    public function createReview(
        User $doctor,
        Patient $patient,
        int $rating,
        ?string $comment = null,
        ?int $appointmentId = null
    ): DoctorReview {
        if (!$doctor->hasRole('doctor')) {
            throw ValidationException::withMessages([
                'doctor_id' => ['The selected user is not a doctor.'],
            ]);
        }

        if ($appointmentId !== null) {
            $appointment = Appointment::find($appointmentId);

            if (!$appointment) {
                throw ValidationException::withMessages([
                    'appointment_id' => ['The selected appointment does not exist.'],
                ]);
            }

            if ((int) $appointment->patient_id !== (int) $patient->id) {
                throw ValidationException::withMessages([
                    'appointment_id' => ['You can only review your own appointments.'],
                ]);
            }

            if ((int) $appointment->doctor_id !== (int) $doctor->id) {
                throw ValidationException::withMessages([
                    'appointment_id' => ['This appointment is not with the selected doctor.'],
                ]);
            }

            if ($appointment->status !== 'completed') {
                throw ValidationException::withMessages([
                    'appointment_id' => ['You can only review completed appointments.'],
                ]);
            }

            if (DoctorReview::where('appointment_id', $appointmentId)->exists()) {
                throw ValidationException::withMessages([
                    'appointment_id' => ['This appointment has already been reviewed.'],
                ]);
            }
        } else {
            $hasCompletedAppointment = Appointment::where('patient_id', $patient->id)
                ->where('doctor_id', $doctor->id)
                ->where('status', 'completed')
                ->exists();

            if (!$hasCompletedAppointment) {
                throw ValidationException::withMessages([
                    'appointment_id' => ['A completed appointment with this doctor is required before reviewing.'],
                ]);
            }
        }

        return DB::transaction(function () use ($doctor, $patient, $rating, $comment, $appointmentId) {
            return DoctorReview::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'appointment_id' => $appointmentId,
                'rating' => $rating,
                'comment' => $comment,
            ]);
        });
    }
}
