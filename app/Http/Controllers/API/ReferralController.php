<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{
    /**
     * Get referrals for a consultation.
     */
    public function index(Request $request, $consultationId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            
            $query = $consultation->referrals()
                ->with(['referredToDoctor', 'referredBy']);

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->filled('urgency')) {
                $query->where('urgency', $request->get('urgency'));
            }

            if ($request->filled('specialty')) {
                $query->where('referred_to_specialty', $request->get('specialty'));
            }

            $referrals = $query->orderBy('referral_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $referrals
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching referrals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new referral.
     */
    public function store(Request $request, $consultationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'referred_to_specialty' => 'required|string|max:100',
            'referred_to_doctor_id' => 'nullable|exists:users,id',
            'reason' => 'required|string|max:1000',
            'urgency' => 'required|in:routine,urgent',
            'notes' => 'nullable|string|max:2000',
            'external_facility' => 'nullable|string|max:255',
            'external_contact' => 'nullable|string|max:255',
            'external_address' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consultation = Consultation::findOrFail($consultationId);

            $referral = Referral::create([
                'consultation_id' => $consultation->id,
                'referred_to_specialty' => $request->referred_to_specialty,
                'referred_to_doctor_id' => $request->referred_to_doctor_id,
                'reason' => $request->reason,
                'urgency' => $request->urgency,
                'notes' => $request->notes,
                'external_facility' => $request->external_facility,
                'external_contact' => $request->external_contact,
                'external_address' => $request->external_address,
                'status' => 'pending',
                'referral_date' => now()->toDateString(),
                'referred_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $referral->load(['referredToDoctor', 'referredBy']),
                'message' => 'Referral created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating referral: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific referral.
     */
    public function show($consultationId, $referralId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $referral = $consultation->referrals()
                ->with(['referredToDoctor', 'referredBy'])
                ->findOrFail($referralId);

            return response()->json([
                'success' => true,
                'data' => $referral
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Referral not found'
            ], 404);
        }
    }

    /**
     * Update a referral.
     */
    public function update(Request $request, $consultationId, $referralId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'referred_to_specialty' => 'nullable|string|max:100',
            'referred_to_doctor_id' => 'nullable|exists:users,id',
            'reason' => 'nullable|string|max:1000',
            'urgency' => 'nullable|in:routine,urgent',
            'notes' => 'nullable|string|max:2000',
            'status' => 'nullable|in:pending,accepted,completed,rejected',
            'external_facility' => 'nullable|string|max:255',
            'external_contact' => 'nullable|string|max:255',
            'external_address' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consultation = Consultation::findOrFail($consultationId);
            $referral = $consultation->referrals()->findOrFail($referralId);

            $updateData = $request->only([
                'referred_to_specialty',
                'referred_to_doctor_id',
                'reason',
                'urgency',
                'notes',
                'status',
                'external_facility',
                'external_contact',
                'external_address'
            ]);

            if ($request->has('status')) {
                if ($request->status === 'accepted' && !$referral->accepted_at) {
                    $updateData['accepted_at'] = now();
                } elseif ($request->status === 'completed' && !$referral->completed_at) {
                    $updateData['completed_at'] = now();
                }
            }

            $referral->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $referral->fresh()->load(['referredToDoctor', 'referredBy']),
                'message' => 'Referral updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating referral: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a referral.
     */
    public function destroy($consultationId, $referralId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $referral = $consultation->referrals()->findOrFail($referralId);

            // Only allow deletion if status is pending
            if ($referral->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete referral that is not pending'
                ], 422);
            }

            $referral->delete();

            return response()->json([
                'success' => true,
                'message' => 'Referral deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting referral: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accept a referral.
     */
    public function accept($consultationId, $referralId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $referral = $consultation->referrals()->findOrFail($referralId);

            $referral->accept();

            return response()->json([
                'success' => true,
                'data' => $referral->fresh()->load(['referredToDoctor', 'referredBy']),
                'message' => 'Referral accepted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error accepting referral: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete a referral.
     */
    public function complete($consultationId, $referralId): JsonResponse
    {
        try {
            $consultation = Consultation::findOrFail($consultationId);
            $referral = $consultation->referrals()->findOrFail($referralId);

            $referral->complete();

            return response()->json([
                'success' => true,
                'data' => $referral->fresh()->load(['referredToDoctor', 'referredBy']),
                'message' => 'Referral completed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error completing referral: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending referrals.
     */
    public function getPending(Request $request): JsonResponse
    {
        try {
            $query = Referral::pending()
                ->with(['consultation.patient', 'referredToDoctor', 'referredBy']);

            if ($request->filled('specialty')) {
                $query->where('referred_to_specialty', $request->get('specialty'));
            }

            if ($request->filled('urgency')) {
                $query->where('urgency', $request->get('urgency'));
            }

            $referrals = $query->orderBy('referral_date', 'asc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $referrals->items(),
                'meta' => [
                    'current_page' => $referrals->currentPage(),
                    'last_page' => $referrals->lastPage(),
                    'per_page' => $referrals->perPage(),
                    'total' => $referrals->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching pending referrals: ' . $e->getMessage()
            ], 500);
        }
    }
}
