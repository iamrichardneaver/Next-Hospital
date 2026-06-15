<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\IcuLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IcuController extends Controller
{
    public function index(Request $request)
    {
        $query = IcuLog::with(['patient', 'visit', 'bed', 'attendingDoctor', 'assignedNurse', 'branch']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('patient_condition')) {
            $query->where('patient_condition', $request->patient_condition);
        }
        
        if ($request->has('active_only')) {
            $query->active();
        }
        
        if ($request->has('critical_only')) {
            $query->critical();
        }
        
        if ($request->has('on_ventilator')) {
            $query->onVentilator();
        }
        
        $icuLogs = $query->latest('admission_time')->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'data' => $icuLogs,
        ]);
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'bed_id' => 'nullable|exists:beds,id',
            'admission_time' => 'required|date',
            'admission_type' => 'required|in:elective,emergency,transfer',
            'admission_diagnosis' => 'nullable|string',
            'chief_complaint' => 'nullable|string',
            'attending_doctor_id' => 'required|exists:users,id',
            'assigned_nurse_id' => 'nullable|exists:users,id',
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
            
            $icuLog = IcuLog::create([
                ...$request->all(),
                'recorded_by' => auth()->id(),
                'recorded_at' => now(),
                'status' => 'active',
            ]);
            
            // Mark bed as occupied if assigned
            if ($request->bed_id) {
                \App\Models\Bed::find($request->bed_id)->update(['status' => 'occupied']);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'ICU admission recorded successfully',
                'data' => $icuLog->load(['patient', 'attendingDoctor', 'assignedNurse']),
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record ICU admission: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function show($id)
    {
        $icuLog = IcuLog::with([
            'patient',
            'visit',
            'bed',
            'attendingDoctor',
            'assignedNurse',
            'recordedBy',
            'branch'
        ])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $icuLog,
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $icuLog = IcuLog::findOrFail($id);
        
        try {
            $icuLog->update([
                ...$request->all(),
                'recorded_by' => auth()->id(),
                'recorded_at' => now(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'ICU log updated successfully',
                'data' => $icuLog->fresh()->load(['patient', 'attendingDoctor']),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ICU log: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function updateVitals(Request $request, $id)
    {
        $icuLog = IcuLog::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'temperature' => 'nullable|numeric',
            'heart_rate' => 'nullable|integer',
            'respiratory_rate' => 'nullable|integer',
            'blood_pressure_systolic' => 'nullable|integer',
            'blood_pressure_diastolic' => 'nullable|integer',
            'oxygen_saturation' => 'nullable|integer|min:0|max:100',
            'glucose_level' => 'nullable|numeric',
            'gcs_eye' => 'nullable|integer|min:1|max:4',
            'gcs_verbal' => 'nullable|integer|min:1|max:5',
            'gcs_motor' => 'nullable|integer|min:1|max:6',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $data = $validator->validated();
            $icuLog->update($data);

            // Calculate GCS total
            if (isset($data['gcs_eye'], $data['gcs_verbal'], $data['gcs_motor'])) {
                $icuLog->update([
                    'gcs_total' => $data['gcs_eye'] + $data['gcs_verbal'] + $data['gcs_motor']
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Vitals updated successfully',
                'data' => $icuLog->fresh(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update vitals: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function discharge(Request $request, $id)
    {
        $icuLog = IcuLog::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'discharge_time' => 'required|date',
            'discharge_notes' => 'nullable|string',
            'discharge_destination' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            $icuLog->update([
                'discharge_time' => $request->discharge_time,
                'discharge_notes' => $request->discharge_notes,
                'discharge_destination' => $request->discharge_destination,
                'status' => 'discharged',
            ]);
            
            // Mark bed as vacant
            if ($icuLog->bed_id) {
                \App\Models\Bed::find($icuLog->bed_id)->update(['status' => 'vacant']);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Patient discharged from ICU successfully',
                'data' => $icuLog->fresh(),
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to discharge patient: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function destroy($id)
    {
        try {
            $icuLog = IcuLog::findOrFail($id);
            $icuLog->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'ICU log deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ICU log: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function getActivePatients()
    {
        $activePatients = IcuLog::active()
            ->with(['patient', 'bed', 'attendingDoctor', 'assignedNurse'])
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $activePatients,
            'count' => $activePatients->count(),
        ]);
    }
    
    public function getCriticalPatients()
    {
        $criticalPatients = IcuLog::active()
            ->critical()
            ->with(['patient', 'bed', 'attendingDoctor', 'assignedNurse'])
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $criticalPatients,
            'count' => $criticalPatients->count(),
        ]);
    }
}

