<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ComplaintController extends Controller
{
    /**
     * Display a listing of complaints
     */
    public function index(Request $request)
    {
        $query = Complaint::with(['patient', 'assignedUser', 'creator', 'branch']);
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }
        
        // Filter by severity
        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }
        
        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('complaint_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('complainant_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        $complaints = $query->latest('id')->paginate($request->per_page ?? 20);
        
        return response()->json([
            'status' => 'success',
            'data' => $complaints,
        ]);
    }
    
    /**
     * Store a newly created complaint
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
                'attachments.*' => 'nullable|file|max:10240',
            ]);
            
            $validated['branch_id'] = $request->user()->staffProfile->branch_id ?? null;
            $validated['created_by'] = $request->user()->id;
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
            
            $complaint->load(['patient', 'assignedUser', 'creator', 'branch']);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Complaint filed successfully!',
                'data' => $complaint,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating complaint: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['attachments']),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create complaint. Please try again.'
            ], 500);
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
        
        return response()->json([
            'status' => 'success',
            'data' => $complaint,
        ]);
    }
    
    /**
     * Update the specified complaint
     */
    public function update(Request $request, Complaint $complaint)
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'nullable|exists:patients,id',
                'complainant_name' => 'sometimes|required|string|max:255',
                'complainant_phone' => 'nullable|string|max:20',
                'complainant_email' => 'nullable|email|max:255',
                'complainant_type' => 'sometimes|required|in:patient,visitor,staff,other',
                'subject' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'category' => 'sometimes|required|in:service_quality,staff_behavior,wait_time,billing,cleanliness,medical_care,facilities,other',
                'severity' => 'sometimes|required|in:low,medium,high,critical',
                'priority' => 'sometimes|required|in:low,normal,high,urgent',
                'status' => 'sometimes|required|in:pending,under_review,investigating,resolved,closed,rejected',
                'assigned_to' => 'nullable|exists:users,id',
                'response' => 'nullable|string',
                'resolution_notes' => 'nullable|string',
                'requires_follow_up' => 'nullable|boolean',
                'follow_up_date' => 'nullable|date',
                'follow_up_notes' => 'nullable|string',
                'attachments.*' => 'nullable|file|max:10240',
            ]);
            
            $validated['updated_by'] = $request->user()->id;
            
            // If status is being changed to resolved, set resolved details
            if (isset($validated['status']) && $validated['status'] === 'resolved' && $complaint->status !== 'resolved') {
                $validated['resolved_at'] = now();
                $validated['resolved_by'] = $request->user()->id;
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
            
            $complaint->load([
                'patient', 
                'assignedUser', 
                'resolvedByUser', 
                'creator', 
                'updater',
                'branch'
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Complaint updated successfully!',
                'data' => $complaint,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating complaint: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'complaint_id' => $complaint->id,
                'request_data' => $request->except(['attachments']),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update complaint. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Remove the specified complaint
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
            
            return response()->json([
                'status' => 'success',
                'message' => 'Complaint deleted successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting complaint: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'complaint_id' => $complaint->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete complaint. Please try again.'
            ], 500);
        }
    }
    
    /**
     * List authenticated patient's own complaints (mobile self-service).
     */
    public function getMyComplaints(Request $request)
    {
        $user = $request->user();
        $patient = $user->patient ?? Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient record not found',
            ], 404);
        }

        $complaints = Complaint::where('patient_id', $patient->id)
            ->latest('id')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $complaints->items(),
            'meta' => [
                'current_page' => $complaints->currentPage(),
                'last_page' => $complaints->lastPage(),
                'total' => $complaints->total(),
            ],
            'message' => 'Complaints retrieved successfully',
        ]);
    }

    /**
     * Submit a complaint as authenticated patient (mobile self-service).
     */
    public function storeMyComplaint(Request $request)
    {
        try {
            $user = $request->user();
            $patient = $user->patient ?? Patient::where('user_id', $user->id)->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient record not found',
                ], 404);
            }

            $validated = $request->validate([
                'subject' => 'required|string|max:255',
                'description' => 'required|string',
                'category' => 'required|in:service_quality,staff_behavior,wait_time,billing,cleanliness,medical_care,facilities,other',
                'severity' => 'nullable|in:low,medium,high,critical',
                'priority' => 'nullable|in:low,normal,high,urgent',
            ]);

            $complaint = Complaint::create([
                'patient_id' => $patient->id,
                'branch_id' => $patient->branch_id,
                'complainant_name' => trim("{$patient->first_name} {$patient->last_name}"),
                'complainant_phone' => $patient->phone,
                'complainant_email' => $patient->email ?? $user->email,
                'complainant_type' => 'patient',
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'severity' => $validated['severity'] ?? 'medium',
                'priority' => $validated['priority'] ?? 'normal',
                'status' => 'pending',
                'created_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Complaint submitted successfully',
                'data' => $complaint,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating patient complaint: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit complaint. Please try again.',
            ], 500);
        }
    }

    /**
     * View a single complaint owned by authenticated patient.
     */
    public function showMyComplaint(Request $request, Complaint $complaint)
    {
        $user = $request->user();
        $patient = $user->patient ?? Patient::where('user_id', $user->id)->first();

        if (!$patient || $complaint->patient_id !== $patient->id) {
            return response()->json([
                'success' => false,
                'message' => 'Complaint not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $complaint,
            'message' => 'Complaint retrieved successfully',
        ]);
    }

    /**
     * Get complaint statistics
     */
    public function statistics()
    {
        $statistics = [
            'total' => Complaint::count(),
            'pending' => Complaint::where('status', 'pending')->count(),
            'under_review' => Complaint::where('status', 'under_review')->count(),
            'investigating' => Complaint::where('status', 'investigating')->count(),
            'resolved' => Complaint::where('status', 'resolved')->count(),
            'closed' => Complaint::where('status', 'closed')->count(),
            'rejected' => Complaint::where('status', 'rejected')->count(),
            'by_category' => Complaint::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category'),
            'by_severity' => Complaint::selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity'),
            'by_priority' => Complaint::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority'),
        ];
        
        return response()->json([
            'status' => 'success',
            'data' => $statistics,
        ]);
    }
}

