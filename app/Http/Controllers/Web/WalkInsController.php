<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsListData;
use App\Models\Visit;
use App\Models\Queue;
use App\Models\Patient;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WalkInsController extends Controller
{
    use ExportsListData;
    /**
     * Display daily walk-ins register
     */
    public function index(Request $request)
    {
        // Permission check
        if (!auth()->check() || !auth()->user()->can('view_walk_ins_register')) {
            abort(403, 'Unauthorized access to walk-ins register');
        }

        // Get user's branch
        $userBranch = auth()->user()->branches()->first();
        $branchId = $request->get('branch_id', $userBranch ? $userBranch->id : null);
        
        if (!$branchId) {
            abort(403, 'User not assigned to any branch');
        }
        $date = $request->get('date', now()->toDateString());
        $visitType = $request->get('visit_type', 'all');
        $status = $request->get('status', 'all');

        // Get all visits for today (excluding orphaned visits). Ensure visit_token and visit_type are loaded for table display.
        $visitsQuery = Visit::with([
                'patient:id,patient_number,first_name,last_name,other_names,gender,date_of_birth,phone,nhis_number',
                'branch:id,name',
                'assignedDoctor:id,first_name,last_name',
                'assignedNurse:id,first_name,last_name',
                'creator:id,first_name,last_name',
                'queues' => function($q) {
                    $q->with(['calledBy:id,first_name,last_name', 'servedBy:id,first_name,last_name'])
                      ->orderBy('created_at', 'asc');
                },
                'consultation:id,visit_id,consultation_date,chief_complaint,consultation_status',
                'emergencyVisit:id,visit_id,triage_level,arrival_mode'
            ])
            ->whereDate('check_in_time', $date)
            ->whereExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('patients')
                      ->whereRaw('patients.id = visits.patient_id');
            })
            ->orderBy('check_in_time', 'desc');

        // Filter by branch
        if ($branchId) {
            $visitsQuery->where('branch_id', $branchId);
        }

        // Filter by visit type
        if ($visitType !== 'all') {
            $visitsQuery->where('visit_type', $visitType);
        }

        // Filter by status
        if ($status !== 'all') {
            $visitsQuery->where('status', $status);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $visitsQuery->whereHas('patient', function($q) use ($search) {
                $q->where('patient_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('nhis_number', 'like', "%{$search}%");
            })
            ->orWhere('visit_token', 'like', "%{$search}%");
        }

        $visits = $visitsQuery->paginate(50);

        // Get statistics for the day
        $stats = $this->getDailyStatistics($branchId, $date);

        // Get branches for filter
        $branches = Branch::select('id', 'name')->where('is_active', true)->get();

        return view('walk-ins.index', compact('visits', 'stats', 'branches', 'branchId', 'date', 'visitType', 'status'));
    }

    /**
     * Get detailed statistics for the day
     */
    private function getDailyStatistics($branchId, $date)
    {
        $query = Visit::whereDate('check_in_time', $date);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $stats = [
            'total_visits' => $query->count(),
            'active_visits' => (clone $query)->where('status', 'active')->count(),
            'completed_visits' => (clone $query)->where('status', 'completed')->count(),
            
            // By visit type
            'opd_visits' => (clone $query)->where('visit_type', 'OPD')->count(),
            'ipd_visits' => (clone $query)->where('visit_type', 'IPD')->count(),
            'emergency_visits' => (clone $query)->where('visit_type', 'Emergency')->count(),
            'lab_only_visits' => (clone $query)->where('visit_type', 'LabOnly')->count(),
            'pharmacy_only_visits' => (clone $query)->where('visit_type', 'PharmacyOnly')->count(),
            
            // Queue statistics
            'waiting_in_queue' => Queue::whereHas('visit', function($q) use ($date, $branchId) {
                    $q->whereDate('check_in_time', $date);
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                })
                ->where('status', 'waiting')
                ->count(),
                
            'being_served' => Queue::whereHas('visit', function($q) use ($date, $branchId) {
                    $q->whereDate('check_in_time', $date);
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                })
                ->where('status', 'serving')
                ->count(),
                
            // Priority statistics
            'urgent_cases' => (clone $query)->where('priority', 'urgent')->count(),
            'critical_cases' => (clone $query)->where('priority', 'critical')->count(),
        ];

        // Average wait time
        $avgWaitTime = Queue::whereHas('visit', function($q) use ($date, $branchId) {
                $q->whereDate('check_in_time', $date);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->whereNotNull('called_at')
            ->get()
            ->map(function($queue) {
                if ($queue->queued_at && $queue->called_at) {
                    return $queue->queued_at->diffInMinutes($queue->called_at);
                }
                return 0;
            })
            ->filter()
            ->avg();

        $stats['avg_wait_time'] = round($avgWaitTime ?? 0);

        return $stats;
    }

    /**
     * Show detailed view of a specific visit
     */
    public function show($id)
    {
        // Permission check
        if (!auth()->check() || !auth()->user()->can('view_walk_ins_register')) {
            abort(403, 'Unauthorized access to walk-ins register');
        }

        $visit = Visit::with([
                'patient:id,patient_number,first_name,last_name,other_names,gender,date_of_birth,phone,nhis_number',
                'branch:id,name',
                'assignedDoctor:id,first_name,last_name',
                'assignedNurse:id,first_name,last_name',
                'creator:id,first_name,last_name',
                'updater:id,first_name,last_name',
                'queues' => function($q) {
                    $q->with(['calledBy:id,first_name,last_name', 'servedBy:id,first_name,last_name'])
                      ->orderBy('created_at', 'asc');
                },
                'consultation:id,visit_id,consultation_date,chief_complaint,consultation_status',
                'bedAssignment.bed.ward',
                'emergencyVisit:id,visit_id,triage_level,arrival_mode'
            ])
            ->findOrFail($id);

        // Calculate queue statistics
        $queueStats = [
            'total_queues' => $visit->queues->count(),
            'waiting' => $visit->queues->where('status', 'waiting')->count(),
            'called' => $visit->queues->where('status', 'called')->count(),
            'serving' => $visit->queues->where('status', 'serving')->count(),
            'completed' => $visit->queues->where('status', 'completed')->count(),
            'cancelled' => $visit->queues->where('status', 'cancelled')->count(),
            'average_wait_time' => $visit->queues->where('status', 'completed')->avg('estimated_wait_time'),
            'current_position' => $visit->queues->where('status', 'waiting')->min('position'),
        ];

        return view('walk-ins.show', compact('visit', 'queueStats'));
    }

    /**
     * Export daily walk-ins report
     */
    public function export(Request $request)
    {
        // Permission check
        if (!auth()->check() || !auth()->user()->can('export_walk_ins_register')) {
            abort(403, 'Unauthorized access to export walk-ins');
        }

        $branchId = $request->get('branch_id', auth()->user()?->branch_id);
        $date = $request->get('date', now()->toDateString());

        // Get visits data
        $visits = Visit::with([
                'patient',
                'branch',
                'assignedDoctor',
                'queues'
            ])
            ->whereDate('check_in_time', $date)
            ->when($branchId, function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('check_in_time', 'asc')
            ->get();

        // Get statistics
        $stats = $this->getDailyStatistics($branchId, $date);

        // Get branding for PDF
        $brandingSettings = \App\Models\BrandingSetting::current();
        $branding = [
            'business_name' => $brandingSettings->business_name ?? env('HOSPITAL_NAME', 'Next Hospital'),
            'business_address' => $brandingSettings->business_address ?? env('HOSPITAL_ADDRESS', 'Hospital Address'),
            'business_phone' => $brandingSettings->business_phone ?? env('HOSPITAL_PHONE', 'Phone Number'),
            'business_email' => $brandingSettings->business_email ?? env('HOSPITAL_EMAIL', 'Email Address'),
            'primary_color' => $brandingSettings->primary_color ?? '#2c5aa0',
            'logo_base64' => $brandingSettings->logo_base64,
            'logo_absolute_path' => $brandingSettings->logo_absolute_path
        ];

        // Generate PDF
        $pdf = \PDF::loadView('walk-ins.export-pdf', compact('visits', 'stats', 'date', 'branding'));
        $pdf->setPaper('A4', 'landscape');
        
        $filename = 'walk-ins-register-' . $date . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Get real-time statistics via AJAX
     */
    public function statistics(Request $request)
    {
        $branchId = $request->get('branch_id', auth()->user()?->branch_id);
        $date = $request->get('date', now()->toDateString());

        $stats = $this->getDailyStatistics($branchId, $date);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get visit workflow timeline
     */
    public function timeline($id)
    {
        $visit = Visit::with([
            'queues.calledBy',
            'queues.servedBy',
            'queues' => function($q) {
                $q->orderBy('created_at', 'asc');
            },
            'consultation',
            'assignedDoctor',
            'bedAssignment'
        ])->findOrFail($id);

        $timeline = [];

        // Check-in
        $timeline[] = [
            'time' => $visit->check_in_time,
            'time_formatted' => $visit->check_in_time->format('d M Y, H:i'),
            'event' => 'Patient Check-in',
            'description' => "Visit Type: {$visit->visit_type}",
            'icon' => 'bi-door-open',
            'color' => 'primary'
        ];

        // Queues
        foreach ($visit->queues as $queue) {
            $timeline[] = [
                'time' => $queue->queued_at,
                'time_formatted' => $queue->queued_at ? $queue->queued_at->format('d M Y, H:i') : null,
                'event' => "Added to {$queue->queue_type} Queue",
                'description' => "Position: {$queue->position}, Priority: {$queue->priority}",
                'icon' => 'bi-people',
                'color' => 'info'
            ];

            if ($queue->called_at) {
                $calledByName = $queue->calledBy ? "{$queue->calledBy->first_name} {$queue->calledBy->last_name}" : 'Unknown';
                $timeline[] = [
                    'time' => $queue->called_at,
                    'time_formatted' => $queue->called_at->format('d M Y, H:i'),
                    'event' => "{$queue->queue_type} - Patient Called",
                    'description' => "Called by: {$calledByName}",
                    'icon' => 'bi-bell',
                    'color' => 'warning'
                ];
            }

            if ($queue->serving_at) {
                $servedByName = $queue->servedBy ? "{$queue->servedBy->first_name} {$queue->servedBy->last_name}" : 'Unknown';
                $timeline[] = [
                    'time' => $queue->serving_at,
                    'time_formatted' => $queue->serving_at->format('d M Y, H:i'),
                    'event' => "{$queue->queue_type} - Service Started",
                    'description' => "Served by: {$servedByName}",
                    'icon' => 'bi-person-check',
                    'color' => 'success'
                ];
            }

            if ($queue->completed_at) {
                $timeline[] = [
                    'time' => $queue->completed_at,
                    'time_formatted' => $queue->completed_at->format('d M Y, H:i'),
                    'event' => "{$queue->queue_type} - Service Completed",
                    'description' => "Duration: " . $queue->queued_at->diffForHumans($queue->completed_at, true),
                    'icon' => 'bi-check-circle',
                    'color' => 'success'
                ];
            }
        }

        // Consultation
        if ($visit->consultation) {
            $doctorName = $visit->assignedDoctor ? "Dr. {$visit->assignedDoctor->first_name} {$visit->assignedDoctor->last_name}" : 'Unassigned';
            $timeline[] = [
                'time' => $visit->consultation->consultation_date ?? $visit->consultation->created_at,
                'time_formatted' => ($visit->consultation->consultation_date ?? $visit->consultation->created_at)->format('d M Y, H:i'),
                'event' => 'Consultation Completed',
                'description' => "Doctor: {$doctorName}",
                'icon' => 'bi-clipboard-pulse',
                'color' => 'primary'
            ];
        }

        // Check-out
        if ($visit->check_out_time) {
            $timeline[] = [
                'time' => $visit->check_out_time,
                'time_formatted' => $visit->check_out_time->format('d M Y, H:i'),
                'event' => 'Patient Check-out',
                'description' => "Total Duration: " . $visit->check_in_time->diffForHumans($visit->check_out_time, true),
                'icon' => 'bi-door-closed',
                'color' => 'secondary'
            ];
        }

        // Sort by time
        usort($timeline, function($a, $b) {
            return $a['time'] <=> $b['time'];
        });

        return response()->json([
            'success' => true,
            'data' => $timeline
        ]);
    }

    public function exportCsv(Request $request)
    {
        if (! auth()->check() || ! auth()->user()->can('export_walk_ins_register')) {
            abort(403, 'Unauthorized access to export walk-ins');
        }

        $userBranch = auth()->user()->branches()->first();
        $branchId = $request->get('branch_id', $userBranch ? $userBranch->id : null);
        $date = $request->get('date', now()->toDateString());
        $visitType = $request->get('visit_type', 'all');
        $status = $request->get('status', 'all');

        $query = Visit::with(['patient', 'branch', 'assignedDoctor', 'queues'])
            ->whereDate('check_in_time', $date)
            ->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('patients')
                    ->whereRaw('patients.id = visits.patient_id');
            })
            ->orderBy('check_in_time', 'asc');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($visitType !== 'all') {
            $query->where('visit_type', $visitType);
        }
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', fn ($pq) => $pq->where('patient_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhere('visit_token', 'like', "%{$search}%");
            });
        }

        return $this->exportFromQuery($request, $query, [
            'Visit Token' => 'visit_token',
            'Patient' => fn ($v) => $v->patient?->full_name ?? '',
            'Patient Number' => fn ($v) => $v->patient?->patient_number ?? '',
            'Visit Type' => 'visit_type',
            'Status' => 'status',
            'Check-in Time' => fn ($v) => $this->formatExportDate($v->check_in_time, 'Y-m-d H:i'),
            'Assigned Doctor' => fn ($v) => $this->formatExportUserName($v->assignedDoctor),
            'Branch' => fn ($v) => $v->branch?->name ?? '',
            'Queue Status' => fn ($v) => $v->queues->pluck('status')->unique()->join(', '),
        ], 'walk-ins-register-'.$date, 'export_walk_ins_register');
    }
}

