<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AppointmentFee;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AppointmentFeeController extends Controller
{
    /**
     * Display a listing of appointment fees.
     */
    public function index(Request $request)
    {
        $query = AppointmentFee::with(['doctor', 'branch', 'creator'])
            ->orderBy('appointment_type')
            ->orderBy('fee_category');

        // Filter by doctor
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by appointment type
        if ($request->has('appointment_type')) {
            $query->where('appointment_type', $request->appointment_type);
        }

        // Filter by fee category
        if ($request->has('fee_category')) {
            $query->where('fee_category', $request->fee_category);
        }

        // Filter by active fees only
        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        // Filter by effective fees only
        if ($request->boolean('effective_only', true)) {
            $query->effective();
        }

        $fees = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $fees,
            'message' => 'Appointment fees retrieved successfully'
        ]);
    }

    /**
     * Store a newly created appointment fee.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'doctor_id' => 'nullable|exists:users,id',
                'branch_id' => 'required|exists:branches,id',
                'appointment_type' => 'required|in:in-person,teleconsultation',
                'fee_category' => 'required|string|max:50',
                'base_fee' => 'required|numeric|min:0',
                'currency' => 'required|string|size:3',
                'platform_fee' => 'numeric|min:0',
                'tax_rate' => 'numeric|min:0|max:100',
                'discount_rules' => 'nullable|array',
                'is_active' => 'boolean',
                'effective_from' => 'nullable|date',
                'effective_until' => 'nullable|date|after:effective_from',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for conflicts
            $conflict = $this->checkFeeConflict(
                $request->doctor_id,
                $request->branch_id,
                $request->appointment_type,
                $request->fee_category,
                $request->effective_from
            );

            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fee conflict detected',
                    'conflict' => $conflict
                ], 409);
            }

            $fee = AppointmentFee::create([
                'doctor_id' => $request->doctor_id,
                'branch_id' => $request->branch_id,
                'appointment_type' => $request->appointment_type,
                'fee_category' => $request->fee_category,
                'base_fee' => $request->base_fee,
                'currency' => $request->currency,
                'platform_fee' => $request->platform_fee ?? 0,
                'tax_rate' => $request->tax_rate ?? 0,
                'discount_rules' => $request->discount_rules,
                'is_active' => $request->boolean('is_active', true),
                'effective_from' => $request->effective_from,
                'effective_until' => $request->effective_until,
                'description' => $request->description,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $fee->load(['doctor', 'branch']),
                'message' => 'Appointment fee created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating appointment fee: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating appointment fee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified appointment fee.
     */
    public function show($id)
    {
        $fee = AppointmentFee::with(['doctor', 'branch', 'creator', 'updater'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $fee,
            'message' => 'Appointment fee retrieved successfully'
        ]);
    }

    /**
     * Update the specified appointment fee.
     */
    public function update(Request $request, $id)
    {
        try {
            $fee = AppointmentFee::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'base_fee' => 'sometimes|numeric|min:0',
                'currency' => 'sometimes|string|size:3',
                'platform_fee' => 'sometimes|numeric|min:0',
                'tax_rate' => 'sometimes|numeric|min:0|max:100',
                'discount_rules' => 'sometimes|array',
                'is_active' => 'sometimes|boolean',
                'effective_from' => 'nullable|date',
                'effective_until' => 'nullable|date|after:effective_from',
                'description' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Use only validated data for security (prevent mass assignment vulnerabilities)
            $fee->update(array_merge($validator->validated(), [
                'updated_by' => auth()->id(),
            ]));

            return response()->json([
                'success' => true,
                'data' => $fee->load(['doctor', 'branch']),
                'message' => 'Appointment fee updated successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment fee not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating appointment fee: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'fee_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating appointment fee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified appointment fee.
     */
    public function destroy($id)
    {
        try {
            $fee = AppointmentFee::findOrFail($id);
            $fee->delete();

            return response()->json([
                'success' => true,
                'message' => 'Appointment fee deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment fee not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting appointment fee: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'fee_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting appointment fee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate appointment fee.
     */
    public function calculateFee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'nullable|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'appointment_type' => 'required|in:in-person,teleconsultation',
            'fee_category' => 'string|max:50',
            'context' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = AppointmentFee::where('branch_id', $request->branch_id)
            ->where('appointment_type', $request->appointment_type)
            ->effective()
            ->active();

        if ($request->has('doctor_id')) {
            $query->where(function($q) use ($request) {
                $q->where('doctor_id', $request->doctor_id)
                  ->orWhereNull('doctor_id');
            });
        } else {
            $query->whereNull('doctor_id');
        }

        if ($request->has('fee_category')) {
            $query->where('fee_category', $request->fee_category);
        }

        $fee = $query->orderBy('doctor_id', 'desc') // Doctor-specific fees first
                    ->first();

        if (!$fee) {
            return response()->json([
                'success' => false,
                'message' => 'No fee structure found for the specified criteria'
            ], 404);
        }

        $context = $request->get('context', []);
        $breakdown = $fee->applyDiscounts($context);

        return response()->json([
            'success' => true,
            'data' => [
                'fee' => $fee,
                'breakdown' => $breakdown,
                'calculated_at' => now()->toISOString(),
            ],
            'message' => 'Fee calculated successfully'
        ]);
    }

    /**
     * Get fee structure for a doctor.
     */
    public function getDoctorFees(Request $request, $doctorId)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'appointment_type' => 'in:in-person,teleconsultation',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = AppointmentFee::where('doctor_id', $doctorId)
            ->where('branch_id', $request->branch_id)
            ->effective()
            ->active();

        if ($request->has('appointment_type')) {
            $query->where('appointment_type', $request->appointment_type);
        }

        $fees = $query->orderBy('appointment_type')
                     ->orderBy('fee_category')
                     ->get();

        return response()->json([
            'success' => true,
            'data' => $fees,
            'message' => 'Doctor fees retrieved successfully'
        ]);
    }

    /**
     * Get fee structure for a branch.
     */
    public function getBranchFees(Request $request, $branchId)
    {
        $validator = Validator::make($request->all(), [
            'appointment_type' => 'in:in-person,teleconsultation',
            'fee_category' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = AppointmentFee::where('branch_id', $branchId)
            ->whereNull('doctor_id') // Branch-level fees only
            ->effective()
            ->active();

        if ($request->has('appointment_type')) {
            $query->where('appointment_type', $request->appointment_type);
        }

        if ($request->has('fee_category')) {
            $query->where('fee_category', $request->fee_category);
        }

        $fees = $query->orderBy('appointment_type')
                     ->orderBy('fee_category')
                     ->get();

        return response()->json([
            'success' => true,
            'data' => $fees,
            'message' => 'Branch fees retrieved successfully'
        ]);
    }

    /**
     * Get fee statistics.
     */
    public function getStatistics(Request $request)
    {
        $query = AppointmentFee::query();

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by appointment type
        if ($request->has('appointment_type')) {
            $query->where('appointment_type', $request->appointment_type);
        }

        $stats = [
            'total_fees' => $query->count(),
            'active_fees' => $query->clone()->active()->count(),
            'effective_fees' => $query->clone()->effective()->count(),
            'doctor_specific_fees' => $query->clone()->whereNotNull('doctor_id')->count(),
            'branch_level_fees' => $query->clone()->whereNull('doctor_id')->count(),
            'in_person_fees' => $query->clone()->where('appointment_type', 'in-person')->count(),
            'teleconsultation_fees' => $query->clone()->where('appointment_type', 'teleconsultation')->count(),
            'average_base_fee' => $query->clone()->avg('base_fee'),
            'average_platform_fee' => $query->clone()->avg('platform_fee'),
            'average_tax_rate' => $query->clone()->avg('tax_rate'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Fee statistics retrieved successfully'
        ]);
    }

    /**
     * Check for fee conflicts.
     */
    private function checkFeeConflict($doctorId, $branchId, $appointmentType, $feeCategory, $effectiveFrom)
    {
        $query = AppointmentFee::where('branch_id', $branchId)
            ->where('appointment_type', $appointmentType)
            ->where('fee_category', $feeCategory);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        } else {
            $query->whereNull('doctor_id');
        }

        if ($effectiveFrom) {
            $query->where(function($q) use ($effectiveFrom) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', $effectiveFrom);
            });
        }

        return $query->first();
    }
}
