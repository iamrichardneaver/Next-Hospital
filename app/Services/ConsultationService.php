<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service class for managing consultation creation and updates
 * 
 * This service centralizes consultation creation logic to ensure
 * consistency across the application and prevent code duplication.
 */
class ConsultationService
{
    /**
     * Create a draft consultation for a visit
     * 
     * @param Visit $visit The visit to create consultation for
     * @param int|null $doctorId Optional doctor ID (uses visit's assigned_doctor_id if not provided)
     * @param array $additionalData Additional data to include in consultation
     * @return Consultation|null The created consultation or null if creation failed
     */
    public function createDraftConsultationForVisit(
        Visit $visit, 
        ?int $doctorId = null, 
        array $additionalData = []
    ): ?Consultation {
        // Use provided doctor_id or visit's assigned_doctor_id
        $doctorId = $doctorId ?? $visit->assigned_doctor_id;
        
        // Validation: Consultation requires a doctor
        if (!$doctorId) {
            Log::warning('Cannot create consultation without doctor', [
                'visit_id' => $visit->id,
                'visit_type' => $visit->visit_type,
            ]);
            return null;
        }
        
        // Only create consultation for visit types that require it
        if (!in_array($visit->visit_type, ['OPD', 'IPD', 'Emergency'])) {
            Log::debug('Visit type does not require consultation', [
                'visit_id' => $visit->id,
                'visit_type' => $visit->visit_type,
            ]);
            return null;
        }
        
        // Check if consultation already exists (non-cancelled)
        $existingConsultation = Consultation::where('visit_id', $visit->id)
            ->where('consultation_status', '!=', 'cancelled')
            ->first();
        
        if ($existingConsultation) {
            Log::debug('Consultation already exists for visit', [
                'visit_id' => $visit->id,
                'consultation_id' => $existingConsultation->id,
            ]);
            return $existingConsultation;
        }
        
        try {
            $consultationData = array_merge([
                'patient_id' => $visit->patient_id,
                'doctor_id' => $doctorId,
                'visit_id' => $visit->id,
                'branch_id' => $visit->branch_id,
                'consultation_date' => $visit->check_in_time->toDateString(),
                'consultation_time' => $visit->check_in_time->format('H:i'),
                'consultation_type' => 'in-person',
                'chief_complaint' => $visit->chief_complaint ?? 'Routine consultation',
                'consultation_status' => 'ongoing',
                'is_draft' => true,
                'urgency' => $visit->priority ?? 'routine',
                'created_by' => $visit->created_by ?? auth()->id(),
            ], $additionalData);
            
            $consultation = Consultation::create($consultationData);
            
            Log::info('Draft consultation created successfully', [
                'consultation_id' => $consultation->id,
                'visit_id' => $visit->id,
                'doctor_id' => $doctorId,
            ]);
            
            return $consultation;
            
        } catch (\Exception $e) {
            Log::error('Failed to create draft consultation', [
                'visit_id' => $visit->id,
                'doctor_id' => $doctorId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return null;
        }
    }
    
    /**
     * Update consultation with vital signs data
     * 
     * @param Consultation $consultation The consultation to update
     * @param array $vitalsData Vital signs data
     * @return bool Success status
     */
    public function updateConsultationWithVitals(Consultation $consultation, array $vitalsData): bool
    {
        try {
            $updateData = [];
            $vitalsFields = [
                'blood_pressure_systolic', 'blood_pressure_diastolic', 'pulse_rate',
                'temperature', 'respiratory_rate', 'oxygen_saturation', 
                'height', 'weight', 'bmi'
            ];
            
            foreach ($vitalsFields as $field) {
                if (isset($vitalsData[$field]) && $vitalsData[$field] !== null) {
                    $updateData[$field] = $vitalsData[$field];
                }
            }
            
            // Ensure consultation remains in draft status
            $updateData['is_draft'] = true;
            $updateData['consultation_status'] = 'ongoing';
            
            $consultation->update($updateData);
            
            Log::info('Consultation updated with vitals', [
                'consultation_id' => $consultation->id,
                'fields_updated' => array_keys($updateData),
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to update consultation with vitals', [
                'consultation_id' => $consultation->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Ensure consultation exists for visit (creates if missing)
     * 
     * @param Visit $visit The visit to check
     * @param int|null $doctorId Optional doctor ID
     * @return Consultation|null The consultation (existing or newly created)
     */
    public function ensureConsultationExists(Visit $visit, ?int $doctorId = null): ?Consultation
    {
        // Check if consultation already exists
        $consultation = Consultation::where('visit_id', $visit->id)
            ->where('consultation_status', '!=', 'cancelled')
            ->first();
        
        if ($consultation) {
            return $consultation;
        }
        
        // Create if missing
        return $this->createDraftConsultationForVisit($visit, $doctorId);
    }
    
    /**
     * Get or create consultation for visit when vitals are recorded
     * 
     * This method handles the common scenario where vitals are recorded
     * and a consultation needs to be created/updated.
     * 
     * @param Visit $visit The visit
     * @param array $vitalsData Vital signs data
     * @param int|null $consultationId Optional existing consultation ID
     * @return Consultation|null The consultation
     */
    public function getOrCreateConsultationForVitals(
        Visit $visit, 
        array $vitalsData = [], 
        ?int $consultationId = null
    ): ?Consultation {
        $consultation = null;
        
        // If consultation_id provided, use it
        if ($consultationId) {
            $consultation = Consultation::find($consultationId);
        }
        
        // If not found, check for existing draft consultation
        if (!$consultation) {
            $consultation = Consultation::where('visit_id', $visit->id)
                ->where('consultation_status', 'ongoing')
                ->where('is_draft', true)
                ->first();
        }
        
        // If still not found, create new consultation (even without assigned doctor)
        // This ensures vitals can always be linked to a consultation
        if (!$consultation) {
            $consultation = $this->createDraftConsultationForVisit($visit);
        }
        
        // Update with vitals if consultation exists
        if ($consultation && !empty($vitalsData)) {
            $this->updateConsultationWithVitals($consultation, $vitalsData);
        }
        
        return $consultation;
    }
}

