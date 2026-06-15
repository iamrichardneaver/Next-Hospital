<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EmergencyAlert;
use App\Models\Patient;
use App\Models\User;
use App\Events\EmergencyAlertSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmergencyAlertController extends Controller
{
    /**
     * Display a listing of emergency alerts (server-side rendering)
     */
    public function index()
    {
        $emergencyAlerts = EmergencyAlert::with(['patient', 'creator', 'acknowledgedBy', 'resolvedBy'])
            ->latest('id')
            ->paginate(20);
        
        $statistics = [
            'total' => EmergencyAlert::count(),
            'active' => EmergencyAlert::where('status', 'active')->count(),
            'acknowledged' => EmergencyAlert::where('status', 'acknowledged')->count(),
            'resolved' => EmergencyAlert::where('status', 'resolved')->count(),
            'critical' => EmergencyAlert::where('priority', 'critical')->count(),
        ];
        
        return view('emergency-alerts.index', compact('emergencyAlerts', 'statistics'));
    }
    
    /**
     * Show the form for creating a new emergency alert
     */
    public function create()
    {
        $patients = Patient::latest()->get();
        
        return view('emergency-alerts.create', compact('patients'));
    }
    
    /**
     * Store a newly created emergency alert in database
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'alert_type' => 'required|string|max:255',
                'priority' => 'required|in:low,medium,high,critical',
                'message' => 'required|string|max:1000',
                'location' => 'nullable|string|max:255',
            ]);
            
            $validated['status'] = 'active';
            $validated['created_by'] = auth()->id();
            
            $emergencyAlert = EmergencyAlert::create($validated);
            
            // Dispatch real-time event
            broadcast(new EmergencyAlertSent($emergencyAlert))->toOthers();
            
            return redirect()->route('emergency-alerts.index')
                ->with('success', 'Emergency alert created successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating emergency alert: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to create emergency alert. Please try again.');
        }
    }
    
    /**
     * Display the specified emergency alert
     */
    public function show(EmergencyAlert $emergencyAlert)
    {
        $emergencyAlert->load(['patient', 'creator', 'acknowledgedBy', 'resolvedBy']);
        
        return view('emergency-alerts.show', compact('emergencyAlert'));
    }
    
    /**
     * Show the form for editing the specified emergency alert
     */
    public function edit(EmergencyAlert $emergencyAlert)
    {
        $patients = Patient::latest()->get();
        
        return view('emergency-alerts.edit', compact('emergencyAlert', 'patients'));
    }
    
    /**
     * Update the specified emergency alert in database
     */
    public function update(Request $request, EmergencyAlert $emergencyAlert)
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'alert_type' => 'required|string|max:255',
                'priority' => 'required|in:low,medium,high,critical',
                'message' => 'required|string|max:1000',
                'location' => 'nullable|string|max:255',
                'status' => 'required|in:active,acknowledged,resolved',
            ]);
            
            $validated['updated_by'] = auth()->id();
            
            $emergencyAlert->update($validated);
            
            return redirect()->route('emergency-alerts.index')
                ->with('success', 'Emergency alert updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating emergency alert: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'emergency_alert_id' => $emergencyAlert->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update emergency alert. Please try again.');
        }
    }
    
    /**
     * Acknowledge an emergency alert
     */
    public function acknowledge(Request $request, EmergencyAlert $emergencyAlert)
    {
        $emergencyAlert->update([
            'status' => 'acknowledged',
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
        ]);
        
        return redirect()->route('emergency-alerts.index')
            ->with('success', 'Emergency alert acknowledged successfully!');
    }
    
    /**
     * Resolve an emergency alert
     */
    public function resolve(Request $request, EmergencyAlert $emergencyAlert)
    {
        $validated = $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);
        
        $emergencyAlert->update([
            'status' => 'resolved',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
            'resolution_notes' => $validated['resolution_notes'] ?? null,
        ]);
        
        return redirect()->route('emergency-alerts.index')
            ->with('success', 'Emergency alert resolved successfully!');
    }
    
    /**
     * Remove the specified emergency alert from database
     */
    public function destroy(EmergencyAlert $emergencyAlert)
    {
        try {
            $emergencyAlert->delete();
            
            return redirect()->route('emergency-alerts.index')
                ->with('success', 'Emergency alert deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting emergency alert: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'emergency_alert_id' => $emergencyAlert->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete emergency alert. Please try again.');
        }
    }
}
