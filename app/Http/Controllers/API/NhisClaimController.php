<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\NhisClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NhisClaimController extends Controller
{
    public function index(Request $request)
    {
        $query = NhisClaim::with(['patient', 'visit', 'invoice', 'doctor', 'preparedBy', 'branch']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('nhis_number')) {
            $query->byNhisNumber($request->nhis_number);
        }
        
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }
        
        if ($request->has('date_from')) {
            $query->where('visit_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->where('visit_date', '<=', $request->date_to);
        }
        
        $claims = $query->latest()->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'data' => $claims,
        ]);
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'nhis_number' => 'required|string',
            'visit_date' => 'required|date',
            'visit_type' => 'required|in:OPD,IPD,emergency,maternity,specialist',
            'diagnosis' => 'nullable|string',
            'icd_code' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'nhis_covered_amount' => 'required|numeric|min:0',
            'patient_copay' => 'required|numeric|min:0',
            'doctor_id' => 'required|exists:users,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            $claim = NhisClaim::create([
                ...$request->all(),
                'prepared_by' => auth()->id(),
                'claimed_amount' => $request->nhis_covered_amount,
                'status' => 'draft',
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'NHIS claim created successfully',
                'data' => $claim->load(['patient', 'doctor', 'preparedBy']),
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create claim: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function show($id)
    {
        $claim = NhisClaim::with([
            'patient',
            'visit',
            'invoice',
            'insurancePolicy',
            'doctor',
            'preparedBy',
            'submittedBy',
            'vettedBy',
            'branch'
        ])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $claim,
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $claim = NhisClaim::findOrFail($id);
        
        // Only allow updates on draft or queried claims
        if (!in_array($claim->status, ['draft', 'queried', 'pending_submission'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update submitted or approved claims',
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'diagnosis' => 'nullable|string',
            'icd_code' => 'nullable|string',
            'procedures' => 'nullable|string',
            'medications' => 'nullable|string',
            'investigations' => 'nullable|string',
            'total_amount' => 'nullable|numeric|min:0',
            'claimed_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $claim->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Claim updated successfully',
                'data' => $claim->fresh()->load(['patient', 'doctor']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update claim: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function submitClaim(Request $request, $id)
    {
        $claim = NhisClaim::findOrFail($id);
        
        if ($claim->status !== 'draft' && $claim->status !== 'pending_submission') {
            return response()->json([
                'success' => false,
                'message' => 'Claim has already been submitted',
            ], 400);
        }
        
        $validator = Validator::make($request->all(), [
            'submission_batch_number' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $claim->update([
                'status' => 'submitted',
                'submission_date' => now(),
                'submitted_by' => auth()->id(),
                'submission_batch_number' => $request->submission_batch_number,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Claim submitted successfully',
                'data' => $claim->fresh(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit claim: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function vetClaim(Request $request, $id)
    {
        $claim = NhisClaim::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:under_review,queried,approved,partially_approved,rejected',
            'approved_amount' => 'nullable|numeric|min:0',
            'rejected_amount' => 'nullable|numeric|min:0',
            'rejection_reason' => 'nullable|string',
            'query_details' => 'nullable|string',
            'query_response_deadline' => 'nullable|date',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $updateData = [
                'status' => $request->status,
                'vetted_by' => auth()->id(),
                'vetting_date' => now(),
            ];
            
            if ($request->has('approved_amount')) {
                $updateData['approved_amount'] = $request->approved_amount;
                $updateData['approval_date'] = now();
                $updateData['outstanding_amount'] = $request->approved_amount;
            }
            
            if ($request->has('rejected_amount')) {
                $updateData['rejected_amount'] = $request->rejected_amount;
            }
            
            if ($request->has('rejection_reason')) {
                $updateData['rejection_reason'] = $request->rejection_reason;
            }
            
            if ($request->has('query_details')) {
                $updateData['query_details'] = $request->query_details;
                $updateData['query_response_deadline'] = $request->query_response_deadline;
            }
            
            $claim->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Claim vetted successfully',
                'data' => $claim->fresh(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to vet claim: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function respondToQuery(Request $request, $id)
    {
        $claim = NhisClaim::findOrFail($id);
        
        if ($claim->status !== 'queried') {
            return response()->json([
                'success' => false,
                'message' => 'Claim is not in queried status',
            ], 400);
        }
        
        $validator = Validator::make($request->all(), [
            'query_response' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $claim->update([
                'query_response' => $request->query_response,
                'query_resolved_at' => now(),
                'status' => 'pending_submission', // Ready for resubmission
                'resubmission_count' => $claim->resubmission_count + 1,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Query response submitted successfully',
                'data' => $claim->fresh(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to respond to query: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function recordPayment(Request $request, $id)
    {
        $claim = NhisClaim::findOrFail($id);
        
        if (!in_array($claim->status, ['approved', 'partially_approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only approved claims can receive payment',
            ], 400);
        }
        
        $validator = Validator::make($request->all(), [
            'paid_amount' => 'required|numeric|min:0',
            'payment_reference' => 'required|string',
            'payment_voucher_number' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $claim->update([
                'paid_amount' => $claim->paid_amount + $request->paid_amount,
                'payment_date' => now(),
                'payment_reference' => $request->payment_reference,
                'payment_voucher_number' => $request->payment_voucher_number,
                'outstanding_amount' => $claim->approved_amount - ($claim->paid_amount + $request->paid_amount),
                'status' => ($claim->paid_amount + $request->paid_amount >= $claim->approved_amount) ? 'paid' : $claim->status,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => $claim->fresh(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function destroy($id)
    {
        try {
            $claim = NhisClaim::findOrFail($id);
            
            // Only allow deletion of draft claims
            if ($claim->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft claims can be deleted',
                ], 403);
            }
            
            $claim->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Claim deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete claim: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function getPendingClaims()
    {
        $pending = NhisClaim::pending()
            ->with(['patient', 'doctor', 'branch'])
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $pending,
            'count' => $pending->count(),
        ]);
    }
    
    public function getQueriedClaims()
    {
        $queried = NhisClaim::queried()
            ->with(['patient', 'doctor', 'branch'])
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $queried,
            'count' => $queried->count(),
        ]);
    }
}

