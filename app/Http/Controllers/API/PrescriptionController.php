<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PrescriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PrescriptionController extends Controller
{
    protected $prescriptionService;

    public function __construct(PrescriptionService $prescriptionService)
    {
        $this->prescriptionService = $prescriptionService;
    }

    /**
     * Get patient prescriptions
     */
    public function getPatientPrescriptions(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Get patient ID - if user is a patient, get from patient relationship
            $patientId = $user->isPatient() && $user->patient ? $user->patient->id : $user->id;
            
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            $status = $request->get('status');

            $prescriptions = $this->prescriptionService->getPatientPrescriptions(
                $patientId,
                $page,
                $limit,
                $status
            );

            return response()->json([
                'success' => true,
                'data' => $prescriptions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get prescriptions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show prescription details
     */
    public function show($prescriptionId): JsonResponse
    {
        try {
            $user = Auth::user();
            $patientId = $user->isPatient() && $user->patient ? $user->patient->id : $user->id;
            
            $prescription = $this->prescriptionService->getPrescriptionDetails(
                $prescriptionId,
                $patientId
            );

            if (!$prescription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prescription not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $prescription
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get prescription details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get prescription medications
     */
    public function getMedications($prescriptionId): JsonResponse
    {
        try {
            $user = Auth::user();
            $patientId = $user->isPatient() && $user->patient ? $user->patient->id : $user->id;
            
            $medications = $this->prescriptionService->getPrescriptionMedications(
                $prescriptionId,
                $patientId
            );

            return response()->json([
                'success' => true,
                'data' => $medications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get medications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download prescription PDF
     */
    public function downloadPdf($prescriptionId): JsonResponse
    {
        try {
            $user = Auth::user();
            $patientId = $user->isPatient() && $user->patient ? $user->patient->id : $user->id;
            
            $pdfUrl = $this->prescriptionService->generatePrescriptionPdf(
                $prescriptionId,
                $patientId
            );

            return response()->json([
                'success' => true,
                'data' => ['pdf_url' => $pdfUrl]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get prescription history
     */
    public function getHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $patientId = $user->isPatient() && $user->patient ? $user->patient->id : $user->id;
            
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $history = $this->prescriptionService->getPrescriptionHistory(
                $patientId,
                $page,
                $limit,
                $startDate,
                $endDate
            );

            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get prescription history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active prescriptions
     */
    public function getActive(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $patientId = $user->isPatient() && $user->patient ? $user->patient->id : $user->id;
            
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);

            $prescriptions = $this->prescriptionService->getActivePrescriptions(
                $patientId,
                $page,
                $limit
            );

            return response()->json([
                'success' => true,
                'data' => $prescriptions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get active prescriptions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get medication reminders
     */
    public function getMedicationReminders(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $patientId = $user->isPatient() && $user->patient ? $user->patient->id : $user->id;
            
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);

            $reminders = $this->prescriptionService->getMedicationReminders(
                $patientId,
                $page,
                $limit
            );

            return response()->json([
                'success' => true,
                'data' => $reminders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get medication reminders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark medication as taken
     */
    public function markMedicationAsTaken(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'prescription_id' => 'required|exists:prescriptions,id',
                'medication_id' => 'required|exists:prescription_medications,id',
                'taken_at' => 'required|date',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $result = $this->prescriptionService->markMedicationAsTaken(
                $request->prescription_id,
                $request->medication_id,
                $user->id,
                $request->taken_at,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark medication as taken: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get medication adherence
     */
    public function getMedicationAdherence(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $prescriptionId = $request->get('prescription_id');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $adherence = $this->prescriptionService->getMedicationAdherence(
                $user->id,
                $prescriptionId,
                $startDate,
                $endDate
            );

            return response()->json([
                'success' => true,
                'data' => $adherence
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get medication adherence: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Report side effects
     */
    public function reportSideEffects(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'prescription_id' => 'required|exists:prescriptions,id',
                'medication_id' => 'required|exists:prescription_medications,id',
                'side_effects' => 'required|string|max:1000',
                'severity' => 'required|in:mild,moderate,severe',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $result = $this->prescriptionService->reportSideEffects(
                $request->prescription_id,
                $request->medication_id,
                $user->id,
                $request->side_effects,
                $request->severity,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to report side effects: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get drug interactions
     */
    public function getDrugInteractions(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'medications' => 'required|array|min:1',
                'medications.*' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $interactions = $this->prescriptionService->getDrugInteractions(
                $request->medications
            );

            return response()->json([
                'success' => true,
                'data' => $interactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get drug interactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Request prescription refill
     */
    public function requestRefill(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'prescription_id' => 'required|exists:prescriptions,id',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $result = $this->prescriptionService->requestRefill(
                $request->prescription_id,
                $user->id,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to request refill: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get prescription statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $statistics = $this->prescriptionService->getPrescriptionStatistics(
                $user->id,
                $startDate,
                $endDate
            );

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get prescription statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}