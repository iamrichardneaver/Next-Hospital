<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ComplaintController extends Controller
{
    /**
     * Display a listing of complaints (server-side rendering)
     */
    public function index()
    {
        $query = Complaint::with(['patient', 'assignedUser', 'creator', 'branch']);

        if ($portalPatient = auth()->user()->patient) {
            if (auth()->user()->isPatient()) {
                $query->where('patient_id', $portalPatient->id);
            }
        }

        $complaints = $query->latest('id')->paginate(20);
        
        $statsQuery = Complaint::query();
        if (auth()->user()->isPatient() && auth()->user()->patient) {
            $statsQuery->where('patient_id', auth()->user()->patient->id);
        }

        $statistics = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'under_review' => (clone $statsQuery)->where('status', 'under_review')->count(),
            'resolved' => (clone $statsQuery)->where('status', 'resolved')->count(),
            'critical' => (clone $statsQuery)->where('severity', 'critical')->count(),
        ];
        
        return view('complaints.index', compact('complaints', 'statistics'));
    }
    
    /**
     * Show the form for creating a new complaint
     */
    public function create()
    {
        if (auth()->user()->isPatient() && auth()->user()->patient) {
            $patients = collect([auth()->user()->patient]);
        } else {
            $patients = Patient::latest('id')->get();
        }
        $staff = User::where('is_active', true)->get();
        
        return view('complaints.create', compact('patients', 'staff'));
    }
    
    /**
     * Store a newly created complaint in database
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'nullable|exists:patients,id',
                'complainant_name' => 'required|string|max:255',
                'complainant_phone' => 'nullable|string|max:20',
                'complainant_email' => 'nullable|email|max:255',
                'complainant_type' => 'required|in:patient,visitor,staff,other',
                'subject' => 'required|string|max:255',
                'description' => 'required|string',
                'category' => 'required|in:service_quality,staff_behavior,wait_time,billing,cleanliness,medical_care,facilities,other',
                'severity' => 'required|in:low,medium,high,critical',
                'priority' => 'required|in:low,normal,high,urgent',
                'assigned_to' => 'nullable|exists:users,id',
                'requires_follow_up' => 'nullable|boolean',
                'follow_up_date' => 'nullable|date',
                'attachments.*' => 'nullable|file|max:10240', // 10MB max per file
            ]);
            
            // Get branch ID from authenticated user
            if (auth()->user()->isPatient() && auth()->user()->patient) {
                $validated['patient_id'] = auth()->user()->patient->id;
                $validated['branch_id'] = auth()->user()->patient->branch_id;
                $validated['complainant_type'] = 'patient';
            } else {
                $validated['branch_id'] = auth()->user()->staffProfile->branch_id ?? null;
            }
            $validated['created_by'] = auth()->id();
            $validated['status'] = 'pending';
            
            // Handle file uploads
            if ($request->hasFile('attachments')) {
                $attachments = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('complaints', 'public');
                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType(),
                    ];
                }
                $validated['attachments'] = $attachments;
            }
            
            $complaint = Complaint::create($validated);
            
            return redirect()->route('complaints.index')
                ->with('success', 'Complaint filed successfully! Complaint Number: ' . $complaint->complaint_number);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating complaint: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token', 'attachments']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to file complaint. Please try again.');
        }
    }
    
    /**
     * Display the specified complaint
     */
    public function show(Complaint $complaint)
    {
        $complaint->load([
            'patient', 
            'assignedUser', 
            'resolvedByUser', 
            'creator', 
            'updater',
            'branch'
        ]);
        
        return view('complaints.show', compact('complaint'));
    }
    
    /**
     * Show the form for editing the specified complaint
     */
    public function edit(Complaint $complaint)
    {
        $patients = Patient::latest('id')->get();
        $staff = User::where('is_active', true)->get();
        
        return view('complaints.edit', compact('complaint', 'patients', 'staff'));
    }
    
    /**
     * Update the specified complaint in database
     */
    public function update(Request $request, Complaint $complaint)
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'nullable|exists:patients,id',
                'complainant_name' => 'required|string|max:255',
                'complainant_phone' => 'nullable|string|max:20',
                'complainant_email' => 'nullable|email|max:255',
                'complainant_type' => 'required|in:patient,visitor,staff,other',
                'subject' => 'required|string|max:255',
                'description' => 'required|string',
                'category' => 'required|in:service_quality,staff_behavior,wait_time,billing,cleanliness,medical_care,facilities,other',
                'severity' => 'required|in:low,medium,high,critical',
                'priority' => 'required|in:low,normal,high,urgent',
                'status' => 'required|in:pending,under_review,investigating,resolved,closed,rejected',
                'assigned_to' => 'nullable|exists:users,id',
                'response' => 'nullable|string',
                'resolution_notes' => 'nullable|string',
                'requires_follow_up' => 'nullable|boolean',
                'follow_up_date' => 'nullable|date',
                'follow_up_notes' => 'nullable|string',
                'attachments.*' => 'nullable|file|max:10240',
            ]);
            
            $validated['updated_by'] = auth()->id();
            
            // If status is being changed to resolved, set resolved details
            if ($validated['status'] === 'resolved' && $complaint->status !== 'resolved') {
                $validated['resolved_at'] = now();
                $validated['resolved_by'] = auth()->id();
            }
            
            // Handle file uploads
            if ($request->hasFile('attachments')) {
                $existingAttachments = $complaint->attachments ?? [];
                $newAttachments = [];
                
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('complaints', 'public');
                    $newAttachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType(),
                    ];
                }
                
                $validated['attachments'] = array_merge($existingAttachments, $newAttachments);
            }
            
            $complaint->update($validated);
            
            return redirect()->route('complaints.index')
                ->with('success', 'Complaint updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating complaint: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'complaint_id' => $complaint->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update complaint. Please try again.');
        }
    }
    
    /**
     * Remove the specified complaint from database
     */
    public function destroy(Complaint $complaint)
    {
        try {
            // Delete associated files
            if ($complaint->attachments) {
                foreach ($complaint->attachments as $attachment) {
                    if (isset($attachment['path'])) {
                        Storage::disk('public')->delete($attachment['path']);
                    }
                }
            }
            
            $complaint->delete();
            
            return redirect()->route('complaints.index')
                ->with('success', 'Complaint deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting complaint: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'complaint_id' => $complaint->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete complaint. Please try again.');
        }
    }
}

