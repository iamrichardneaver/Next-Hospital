<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BloodDonation;
use App\Models\BloodInventory;
use App\Models\Transfusion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BloodBankController extends Controller
{
    // ========== BLOOD DONATIONS ==========
    
    public function indexDonations(Request $request)
    {
        $query = BloodDonation::with(['donor', 'collectedBy', 'branch']);
        
        if ($request->has('blood_group')) {
            $query->byBloodGroup($request->blood_group);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('expiring_soon')) {
            $query->expiringSoon($request->expiring_soon ?? 7);
        }
        
        $donations = $query->latest()->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'data' => $donations,
        ]);
    }
    
    public function storeDonation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'donor_id' => 'nullable|exists:patients,id',
            'donor_name' => 'required|string|max:255',
            'donor_phone' => 'nullable|string|max:20',
            'blood_group' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'volume_ml' => 'required|numeric|min:1',
            'donation_date' => 'required|date',
            'donation_time' => 'nullable',
            'blood_bag_number' => 'nullable|string|unique:blood_donations,blood_bag_number',
            'screening_notes' => 'nullable|string',
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
            
            $donation = BloodDonation::create($request->all());
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Blood donation recorded successfully',
                'data' => $donation->load(['donor', 'collectedBy']),
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record donation: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function showDonation($id)
    {
        $donation = BloodDonation::with(['donor', 'collectedBy', 'testedBy', 'approvedBy', 'branch'])
            ->findOrFail($id);
            
        return response()->json([
            'success' => true,
            'data' => $donation,
        ]);
    }
    
    public function updateDonation(Request $request, $id)
    {
        $donation = BloodDonation::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,tested,approved,rejected,used,expired',
            'hiv_test' => 'nullable|in:positive,negative,pending',
            'hbv_test' => 'nullable|in:positive,negative,pending',
            'hcv_test' => 'nullable|in:positive,negative,pending',
            'syphilis_test' => 'nullable|in:positive,negative,pending',
            'tested_by' => 'nullable|exists:users,id',
            'approved_by' => 'nullable|exists:users,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $donation->update($validator->validated());

            // Auto-approve if all tests pass
            if ($donation->all_tests_passed && $donation->status === 'tested') {
                $donation->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);
                
                // Update inventory
                $this->updateInventoryFromDonation($donation);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Donation updated successfully',
                'data' => $donation->fresh()->load(['donor', 'testedBy', 'approvedBy']),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update donation: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function destroyDonation($id)
    {
        try {
            $donation = BloodDonation::findOrFail($id);
            $donation->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Donation deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete donation: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    // ========== BLOOD INVENTORY ==========
    
    public function indexInventory(Request $request)
    {
        $query = BloodInventory::with(['branch']);
        
        if ($request->has('blood_group')) {
            $query->byBloodGroup($request->blood_group);
        }
        
        if ($request->has('component')) {
            $query->byComponent($request->component);
        }
        
        if ($request->has('low_stock')) {
            $query->lowStock();
        }
        
        $inventory = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }
    
    public function showInventory($id)
    {
        $inventory = BloodInventory::with(['branch', 'lastUpdatedBy'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }
    
    public function updateInventory(Request $request, $id)
    {
        $inventory = BloodInventory::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'minimum_stock_level' => 'sometimes|numeric|min:0',
            'optimal_stock_level' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $inventory->update([
                ...$request->only(['minimum_stock_level', 'optimal_stock_level', 'notes']),
                'last_updated_at' => now(),
                'last_updated_by' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Inventory updated successfully',
                'data' => $inventory->fresh(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update inventory: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function getLowStockAlerts(Request $request)
    {
        $lowStock = BloodInventory::lowStock()->with(['branch'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $lowStock,
            'count' => $lowStock->count(),
        ]);
    }
    
    // ========== TRANSFUSIONS ==========
    
    public function indexTransfusions(Request $request)
    {
        $query = Transfusion::with(['patient', 'doctor', 'donation', 'branch']);
        
        if ($request->has('patient_id')) {
            $query->byPatient($request->patient_id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $transfusions = $query->latest()->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'data' => $transfusions,
        ]);
    }
    
    public function storeTransfusion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'consultation_id' => 'nullable|exists:consultations,id',
            'blood_group_patient' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'blood_group_donor' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'blood_component' => 'required|in:whole_blood,packed_cells,plasma,platelets,cryoprecipitate',
            'volume_ml' => 'required|numeric|min:1',
            'indication' => 'nullable|string',
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
            
            $transfusion = Transfusion::create($request->all());
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Transfusion order created successfully',
                'data' => $transfusion->load(['patient', 'doctor']),
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transfusion order: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function showTransfusion($id)
    {
        $transfusion = Transfusion::with([
            'patient', 
            'visit', 
            'consultation', 
            'donation', 
            'doctor', 
            'administeredBy', 
            'crossMatchedBy',
            'branch'
        ])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $transfusion,
        ]);
    }
    
    public function updateTransfusion(Request $request, $id)
    {
        $transfusion = Transfusion::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:pending,completed,cancelled',
            'notes' => 'nullable|string',
            'transfusion_started_at' => 'nullable|date',
            'transfusion_completed_at' => 'nullable|date',
            'adverse_reactions' => 'nullable|string',
            'reaction_severity' => 'nullable|string',
            'pre_transfusion_vitals' => 'nullable|array',
            'post_transfusion_vitals' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $transfusion->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Transfusion updated successfully',
                'data' => $transfusion->fresh()->load(['patient', 'doctor', 'administeredBy']),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update transfusion: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    // Helper method
    private function updateInventoryFromDonation(BloodDonation $donation)
    {
        $inventory = BloodInventory::firstOrCreate(
            [
                'blood_group' => $donation->blood_group,
                'blood_component' => 'whole_blood',
                'branch_id' => $donation->branch_id,
            ],
            [
                'total_units' => 0,
                'available_units' => 0,
                'reserved_units' => 0,
                'used_units' => 0,
                'expired_units' => 0,
            ]
        );
        
        $units = $donation->volume_ml / 450; // Standard unit is 450ml
        $inventory->addUnits($units);
    }
}

