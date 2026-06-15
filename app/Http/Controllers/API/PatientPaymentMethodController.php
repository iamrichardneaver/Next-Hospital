<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PatientPaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PatientPaymentMethodController extends Controller
{
    /**
     * Get all payment methods for authenticated patient
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $patient = $user->patient;
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found'
                ], 404);
            }

            $paymentMethods = PatientPaymentMethod::where('patient_id', $patient->id)
                ->where('is_active', true)
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $paymentMethods,
                'message' => 'Payment methods retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment methods: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add new payment method
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $patient = $user->patient;
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'payment_type' => 'required|in:card,mobile_money,bank_account',
                'provider' => 'nullable|string|max:100',
                'account_name' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:100',
                'card_last_four' => 'nullable|string|size:4',
                'card_brand' => 'nullable|string|max:50',
                'is_default' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // If this is set as default, remove default from other methods
            if ($request->is_default) {
                PatientPaymentMethod::where('patient_id', $patient->id)
                    ->update(['is_default' => false]);
            }

            $paymentMethod = PatientPaymentMethod::create([
                'patient_id' => $patient->id,
                'payment_type' => $request->payment_type,
                'provider' => $request->provider,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'card_last_four' => $request->card_last_four,
                'card_brand' => $request->card_brand,
                'is_default' => $request->is_default ?? false,
            ]);

            return response()->json([
                'success' => true,
                'data' => $paymentMethod,
                'message' => 'Payment method added successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding payment method: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set payment method as default
     */
    public function setDefault(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $patient = $user->patient;
            
            $paymentMethod = PatientPaymentMethod::where('patient_id', $patient->id)
                ->findOrFail($id);

            // Remove default from all other methods
            PatientPaymentMethod::where('patient_id', $patient->id)
                ->update(['is_default' => false]);

            // Set this one as default
            $paymentMethod->update(['is_default' => true]);

            return response()->json([
                'success' => true,
                'data' => $paymentMethod,
                'message' => 'Default payment method updated'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating default: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete payment method
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $patient = $user->patient;
            
            $paymentMethod = PatientPaymentMethod::where('patient_id', $patient->id)
                ->findOrFail($id);

            $paymentMethod->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment method deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting payment method: ' . $e->getMessage()
            ], 500);
        }
    }
}

