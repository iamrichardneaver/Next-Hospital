<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BedAssignment;
use App\Models\Bed;
use App\Models\Ward;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BedAssignmentController extends Controller
{
    /**
     * Display a listing of bed assignments.
     */
    public function index(Request $request)
    {
        $query = BedAssignment::with(['patient', 'bed.ward', 'assignedBy'])
            ->orderBy('admission_date', 'desc');

        // Filter by ward
        if ($request->has('ward_id')) {
            $query->whereHas('bed', function($q) use ($request) {
                $q->where('ward_id', $request->ward_id);
            });
        }

        // Filter by bed
        if ($request->has('bed_id')) {
            $query->where('bed_id', $request->bed_id);
        }

        // Filter by patient
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by patient name or bed number
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('patient', function($patientQuery) use ($search) {
                    $patientQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->orWhereHas('bed', function($bedQuery) use ($search) {
                    $bedQuery->where('bed_number', 'like', "%{$search}%");
                });
            });
        }

        $assignments = $query->paginate(20);

        // Transform the data to match frontend expectations
        $transformedAssignments = $assignments->getCollection()->map(function ($assignment) {
            return [
                'id' => $assignment->id,
                'patient_id' => $assignment->patient_id,
                'patient_name' => $assignment->patient ? $assignment->patient->first_name . ' ' . $assignment->patient->last_name : 'Unknown Patient',
                'bed_id' => $assignment->bed_id,
                'bed_number' => $assignment->bed ? $assignment->bed->bed_number : 'Unknown Bed',
                'ward_name' => $assignment->bed && $assignment->bed->ward ? $assignment->bed->ward->name : 'Unknown Ward',
                'admission_date' => $assignment->admission_date,
                'discharge_date' => $assignment->discharge_date,
                'admission_reason' => $assignment->admission_reason,
                'assigned_by' => $assignment->assignedBy ? $assignment->assignedBy->first_name . ' ' . $assignment->assignedBy->last_name : 'Unknown',
                'status' => $assignment->status,
                'notes' => $assignment->notes,
                'created_at' => $assignment->created_at,
                'updated_at' => $assignment->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedAssignments,
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total(),
            ],
            'message' => 'Bed assignments retrieved successfully'
        ]);
    }

    /**
     * Store a newly created bed assignment.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'bed_id' => 'required|exists:beds,id',
            'admission_date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if bed is vacant
        $bed = Bed::findOrFail($request->bed_id);
        if ($bed->status !== 'vacant') {
            return response()->json([
                'success' => false,
                'message' => 'Bed is not vacant for assignment'
            ], 400);
        }

        // Check if patient already has an active assignment
        $existingAssignment = BedAssignment::where('patient_id', $request->patient_id)
            ->where('status', 'active')
            ->first();

        if ($existingAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'Patient already has an active bed assignment'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Create bed assignment
            $assignment = BedAssignment::create([
                'patient_id' => $request->patient_id,
                'bed_id' => $request->bed_id,
                'ward_id' => $bed->ward_id,
                'admission_date' => $request->admission_date,
                'notes' => $request->notes,
                'assigned_by' => auth()->id(),
                'status' => 'active'
            ]);

            // Update bed status to occupied
            $bed->update([
                'status' => 'occupied'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $assignment->load(['patient', 'bed.ward', 'assignedBy']),
                'message' => 'Bed assignment created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating bed assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified bed assignment.
     */
    public function show($id)
    {
        $assignment = BedAssignment::with(['patient', 'bed.ward', 'assignedBy'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $assignment,
            'message' => 'Bed assignment retrieved successfully'
        ]);
    }

    /**
     * Update the specified bed assignment.
     */
    public function update(Request $request, $id)
    {
        $assignment = BedAssignment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'admission_reason' => 'sometimes|string|max:1000',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:active,discharged,transferred'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $assignment->update(array_merge(
            $request->only(['admission_reason', 'notes', 'status']),
            ['updated_by' => auth()->id()]
        ));

        return response()->json([
            'success' => true,
            'data' => $assignment->load(['patient', 'bed.ward', 'assignedBy']),
            'message' => 'Bed assignment updated successfully'
        ]);
    }

    /**
     * Remove the specified bed assignment.
     */
    public function destroy($id)
    {
        $assignment = BedAssignment::findOrFail($id);

        if ($assignment->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete active bed assignment. Please discharge patient first.'
            ], 400);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bed assignment deleted successfully'
        ]);
    }

    /**
     * Transfer patient to different bed.
     */
    public function transfer(Request $request, $id)
    {
        $assignment = BedAssignment::findOrFail($id);

        if ($assignment->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Can only transfer active assignments'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'new_bed_id' => 'required|exists:beds,id',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $newBed = Bed::findOrFail($request->new_bed_id);

        if ($newBed->status !== 'vacant') {
            return response()->json([
                'success' => false,
                'message' => 'New bed is not vacant'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Free up the old bed
            $oldBed = $assignment->bed;
            $oldBed->update([
                'status' => 'vacant',
                'patient_id' => null,
                'admission_date' => null
            ]);

            // Assign to new bed
            $newBed->update([
                'status' => 'occupied',
                'patient_id' => $assignment->patient_id,
                'admission_date' => $assignment->admission_date
            ]);

            // Update assignment
            $assignment->update([
                'bed_id' => $request->new_bed_id,
                'notes' => $assignment->notes . "\nTransferred to bed " . $newBed->bed_number . " on " . now()->format('Y-m-d H:i:s') . ". " . ($request->notes ?? ''),
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $assignment->load(['patient', 'bed.ward', 'assignedBy']),
                'message' => 'Patient transferred successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error transferring patient: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Discharge patient from bed.
     */
    public function discharge(Request $request, $id)
    {
        $assignment = BedAssignment::findOrFail($id);

        if ($assignment->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Assignment is not active'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'discharge_date' => 'required|date|after_or_equal:admission_date',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Update assignment
            $assignment->update([
                'discharge_date' => $request->discharge_date,
                'status' => 'discharged',
                'notes' => $assignment->notes . "\nDischarged on " . $request->discharge_date . ". " . ($request->notes ?? ''),
                'updated_by' => auth()->id()
            ]);

            // Free up the bed
            $bed = $assignment->bed;
            $bed->update([
                'status' => 'vacant'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $assignment->load(['patient', 'bed.ward', 'assignedBy']),
                'message' => 'Patient discharged successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error discharging patient: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admit patient from visit.
     */
    public function admitFromVisit(Request $request, $visitId)
    {
        $visit = Visit::with(['patient', 'queues'])->findOrFail($visitId);

        if ($visit->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Visit is not active'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'bed_id' => 'required|exists:beds,id',
            'admission_reason' => 'required|string|max:1000',
            'assigned_doctor_id' => 'nullable|exists:users,id',
            'assigned_nurse_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if bed is vacant
        $bed = Bed::findOrFail($request->bed_id);
        if ($bed->status !== 'vacant') {
            return response()->json([
                'success' => false,
                'message' => 'Bed is not vacant for assignment'
            ], 400);
        }

        // Check if patient already has an active assignment
        $existingAssignment = BedAssignment::where('patient_id', $visit->patient_id)
            ->where('status', 'active')
            ->first();

        if ($existingAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'Patient already has an active bed assignment'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Create bed assignment
            $assignment = BedAssignment::create([
                'patient_id' => $visit->patient_id,
                'bed_id' => $request->bed_id,
                'ward_id' => $bed->ward_id,
                'admission_date' => now(),
                'admission_reason' => $request->admission_reason,
                'notes' => $request->notes,
                'assigned_by' => auth()->id(),
                'status' => 'active'
            ]);

            // Update bed status to occupied
            $bed->update([
                'status' => 'occupied',
                'patient_id' => $visit->patient_id,
                'admission_date' => now()
            ]);

            // Update visit type to IPD
            $visit->update([
                'visit_type' => 'IPD',
                'assigned_doctor_id' => $request->assigned_doctor_id,
                'assigned_nurse_id' => $request->assigned_nurse_id,
                'updated_by' => auth()->id()
            ]);

            // Update queue status to completed
            $visit->queues()->where('queue_type', 'OPD')->update([
                'status' => 'completed',
                'completed_at' => now(),
                'served_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $assignment->load(['patient', 'bed.ward', 'assignedBy']),
                'message' => 'Patient admitted successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error admitting patient: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vacant beds for admission.
     */
    public function getAvailableBeds(Request $request)
    {
        $query = Bed::with(['ward'])
            ->where('status', 'vacant');

        // Filter by ward
        if ($request->has('ward_id')) {
            $query->where('ward_id', $request->ward_id);
        }

        // Filter by bed type
        if ($request->has('bed_type')) {
            $query->where('bed_type', $request->bed_type);
        }

        $beds = $query->orderBy('ward_id')->orderBy('bed_number')->get();

        return response()->json([
            'success' => true,
            'data' => $beds,
            'message' => 'Available beds retrieved successfully'
        ]);
    }

    /**
     * Get admission statistics.
     */
    public function getAdmissionStatistics(Request $request)
    {
        $branchId = $request->get('branch_id', 1);
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $stats = [
            'total_admissions' => BedAssignment::whereBetween('admission_date', [$dateFrom, $dateTo])
                ->whereHas('bed.ward', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->count(),
            'active_admissions' => BedAssignment::where('status', 'active')
                ->whereHas('bed.ward', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->count(),
            'discharges' => BedAssignment::where('status', 'discharged')
                ->whereBetween('discharge_date', [$dateFrom, $dateTo])
                ->whereHas('bed.ward', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->count(),
            'average_stay_duration' => BedAssignment::where('status', 'discharged')
                ->whereBetween('discharge_date', [$dateFrom, $dateTo])
                ->whereHas('bed.ward', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->selectRaw('AVG(DATEDIFF(discharge_date, admission_date)) as avg_days')
                ->value('avg_days') ?? 0,
            'by_ward' => BedAssignment::whereBetween('admission_date', [$dateFrom, $dateTo])
                ->whereHas('bed.ward', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->join('beds', 'bed_assignments.bed_id', '=', 'beds.id')
                ->join('wards', 'beds.ward_id', '=', 'wards.id')
                ->selectRaw('wards.name, COUNT(*) as count')
                ->groupBy('wards.id', 'wards.name')
                ->pluck('count', 'name')
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Admission statistics retrieved successfully'
        ]);
    }
}
