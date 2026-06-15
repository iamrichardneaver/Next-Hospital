<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Models\EmergencyVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmergencyController extends Controller
{
    use ExportsListData;
    /**
     * Display a listing of emergency visits (server-side rendering)
     */
    public function index()
    {
        $emergencyVisits = EmergencyVisit::with(['patient', 'assignedDoctor', 'assignedNurse', 'branch'])
            ->latest('id')
            ->paginate(20);
        
        $statistics = [
            'total' => EmergencyVisit::count(),
            'active' => EmergencyVisit::where('status', 'active')->count(),
            'completed' => EmergencyVisit::where('status', 'completed')->count(),
            'today' => EmergencyVisit::whereDate('created_at', today())->count(),
            'critical' => EmergencyVisit::where('triage_level', 'critical')->count(),
        ];
        
        return view('emergency.index', compact('emergencyVisits', 'statistics'));
    }
    
    /**
     * Show the form for creating a new emergency visit
     */
    public function create()
    {
        $patients = Patient::latest()->get();
        $doctors = User::role('doctor')->get();
        $nurses = User::role('nurse')->get();
        $branches = Branch::where('is_active', true)->get();
        
        return view('emergency.create', compact('patients', 'doctors', 'nurses', 'branches'));
    }
    
    /**
     * Store a newly created emergency visit in database
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'chief_complaint' => 'required|string|max:1000',
                'triage_level' => 'required|in:critical,urgent,stable',
                'vital_signs' => 'nullable|array',
                'assigned_doctor_id' => 'nullable|exists:users,id',
                'assigned_nurse_id' => 'nullable|exists:users,id',
                'branch_id' => 'required|exists:branches,id',
            ]);
            
            $validated['status'] = 'active';
            $validated['created_by'] = auth()->id();
            
            $emergencyVisit = EmergencyVisit::create($validated);
            
            return redirect()->route('emergency.index')
                ->with('success', 'Emergency visit created successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating emergency visit: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to create emergency visit. Please try again.');
        }
    }
    
    /**
     * Display the specified emergency visit
     */
    public function show(EmergencyVisit $emergency)
    {
        $emergency->load(['patient', 'assignedDoctor', 'assignedNurse', 'branch', 'interventions', 'creator']);
        
        return view('emergency.show', compact('emergency'));
    }
    
    /**
     * Show the form for editing the specified emergency visit
     */
    public function edit(EmergencyVisit $emergency)
    {
        $doctors = User::role('doctor')->get();
        $nurses = User::role('nurse')->get();
        $branches = Branch::where('is_active', true)->get();
        
        return view('emergency.edit', compact('emergency', 'doctors', 'nurses', 'branches'));
    }
    
    /**
     * Update the specified emergency visit in database
     */
    public function update(Request $request, EmergencyVisit $emergency)
    {
        try {
            $validated = $request->validate([
                'chief_complaint' => 'required|string|max:1000',
                'triage_level' => 'required|in:critical,urgent,stable',
                'vital_signs' => 'nullable|array',
                'assigned_doctor_id' => 'nullable|exists:users,id',
                'assigned_nurse_id' => 'nullable|exists:users,id',
                'status' => 'required|in:active,completed,cancelled',
            ]);
            
            $validated['updated_by'] = auth()->id();
            
            $emergency->update($validated);
            
            return redirect()->route('emergency.index')
                ->with('success', 'Emergency visit updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating emergency visit: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'emergency_id' => $emergency->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update emergency visit. Please try again.');
        }
    }
    
    /**
     * Remove the specified emergency visit from database
     */
    public function destroy(EmergencyVisit $emergency)
    {
        try {
            $emergency->delete();
            
            return redirect()->route('emergency.index')
                ->with('success', 'Emergency visit deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting emergency visit: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'emergency_id' => $emergency->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete emergency visit. They may have existing records.');
        }
    }

    public function export(Request $request)
    {
        $query = EmergencyVisit::with(['patient', 'assignedDoctor', 'branch'])->latest('id');

        return $this->exportFromQuery($request, $query, [
            'Patient' => fn ($e) => $e->patient?->full_name ?? '',
            'Patient Number' => fn ($e) => $e->patient?->patient_number ?? '',
            'Triage Level' => 'triage_level',
            'Status' => 'status',
            'Chief Complaint' => 'chief_complaint',
            'Assigned Doctor' => fn ($e) => $this->formatExportUserName($e->assignedDoctor),
            'Branch' => fn ($e) => $e->branch?->name ?? '',
            'Created At' => fn ($e) => $this->formatExportDate($e->created_at, 'Y-m-d H:i'),
        ], 'emergency-visits', 'view_emergency_visits');
    }
}
