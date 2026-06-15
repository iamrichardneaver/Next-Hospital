<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Consultation;
use App\Models\LabRequest;
use App\Models\LabResult;
use App\Models\Invoice;
use App\Models\EmergencyVisit;
use App\Models\SurgerySchedule;
use App\Models\Appointment;
use App\Models\NhisClaim;
use App\Models\GhsReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportingController extends Controller
{
    /**
     * Generate patient report.
     */
    public function generatePatientReport(Request $request, $patientId)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:summary,medical_history,lab_results,prescriptions,visits',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'format' => 'nullable|in:pdf,excel,json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $patient = Patient::with(['consultations', 'labRequests.results', 'prescriptions', 'emergencyVisits'])
            ->findOrFail($patientId);

        $dateFrom = $request->date_from ?? now()->subYear();
        $dateTo = $request->date_to ?? now();

        $reportData = $this->compilePatientReportData($patient, $request->report_type, $dateFrom, $dateTo);

        if ($request->format === 'pdf') {
            return $this->generatePDFReport($reportData, 'patient-report');
        } elseif ($request->format === 'excel') {
            return $this->generateExcelReport($reportData, 'patient-report');
        }

        return response()->json([
            'success' => true,
            'data' => $reportData,
            'message' => 'Patient report generated successfully'
        ]);
    }

    /**
     * Generate lab results report.
     */
    public function generateLabReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'test_type' => 'nullable|string',
            'status' => 'nullable|in:pending,completed,cancelled',
            'format' => 'nullable|in:pdf,excel,json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = LabRequest::with(['patient', 'results', 'doctor'])
            ->whereBetween('created_at', [$request->date_from, $request->date_to]);

        if ($request->has('test_type')) {
            $query->where('test_type', $request->test_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $labRequests = $query->get();

        $reportData = [
            'title' => 'Laboratory Results Report',
            'period' => "{$request->date_from} to {$request->date_to}",
            'total_requests' => $labRequests->count(),
            'completed_requests' => $labRequests->where('status', 'completed')->count(),
            'pending_requests' => $labRequests->where('status', 'pending')->count(),
            'requests' => $labRequests,
            'test_types' => $this->getTestTypeStatistics($labRequests),
            'abnormal_results' => $this->getAbnormalResults($labRequests)
        ];

        if ($request->format === 'pdf') {
            return $this->generatePDFReport($reportData, 'lab-report');
        } elseif ($request->format === 'excel') {
            return $this->generateExcelReport($reportData, 'lab-report');
        }

        return response()->json([
            'success' => true,
            'data' => $reportData,
            'message' => 'Lab report generated successfully'
        ]);
    }

    /**
     * Generate financial report.
     */
    public function generateFinancialReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'report_type' => 'required|in:revenue,expenses,profit_loss,payment_methods',
            'format' => 'nullable|in:pdf,excel,json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $reportData = $this->compileFinancialReportData($request->report_type, $request->date_from, $request->date_to);

        if ($request->format === 'pdf') {
            return $this->generatePDFReport($reportData, 'financial-report');
        } elseif ($request->format === 'excel') {
            return $this->generateExcelReport($reportData, 'financial-report');
        }

        return response()->json([
            'success' => true,
            'data' => $reportData,
            'message' => 'Financial report generated successfully'
        ]);
    }

    /**
     * Generate NHIS report.
     */
    public function generateNHISReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|date_format:Y-m',
            'format' => 'nullable|in:pdf,excel,json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $month = Carbon::parse($request->month);
        $startDate = $month->startOfMonth();
        $endDate = $month->endOfMonth();

        $reportData = $this->compileNHISReportData($startDate, $endDate);

        if ($request->format === 'pdf') {
            return $this->generatePDFReport($reportData, 'nhis-report');
        } elseif ($request->format === 'excel') {
            return $this->generateExcelReport($reportData, 'nhis-report');
        }

        return response()->json([
            'success' => true,
            'data' => $reportData,
            'message' => 'NHIS report generated successfully'
        ]);
    }

    /**
     * Generate GHS report.
     */
    public function generateGHSReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|date_format:Y-m',
            'report_type' => 'required|in:malaria,tb,hiv,child_welfare,antenatal,general',
            'format' => 'nullable|in:pdf,excel,json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $month = Carbon::parse($request->month);
        $startDate = $month->startOfMonth();
        $endDate = $month->endOfMonth();

        $reportData = $this->compileGHSReportData($request->report_type, $startDate, $endDate);

        if ($request->format === 'pdf') {
            return $this->generatePDFReport($reportData, 'ghs-report');
        } elseif ($request->format === 'excel') {
            return $this->generateExcelReport($reportData, 'ghs-report');
        }

        return response()->json([
            'success' => true,
            'data' => $reportData,
            'message' => 'GHS report generated successfully'
        ]);
    }

    /**
     * Generate emergency department report.
     */
    public function generateEmergencyReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'triage_level' => 'nullable|in:1,2,3,4,5',
            'format' => 'nullable|in:pdf,excel,json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = EmergencyVisit::with(['patient', 'triageAssessment', 'assignedDoctor'])
            ->whereBetween('arrival_time', [$request->date_from, $request->date_to]);

        if ($request->has('triage_level')) {
            $query->where('triage_level', $request->triage_level);
        }

        $visits = $query->get();

        $reportData = [
            'title' => 'Emergency Department Report',
            'period' => "{$request->date_from} to {$request->date_to}",
            'total_visits' => $visits->count(),
            'triage_levels' => $this->getTriageLevelStatistics($visits),
            'arrival_modes' => $this->getArrivalModeStatistics($visits),
            'top_complaints' => $this->getTopComplaints($visits),
            'average_wait_time' => $this->getAverageWaitTime($visits),
            'visits' => $visits
        ];

        if ($request->format === 'pdf') {
            return $this->generatePDFReport($reportData, 'emergency-report');
        } elseif ($request->format === 'excel') {
            return $this->generateExcelReport($reportData, 'emergency-report');
        }

        return response()->json([
            'success' => true,
            'data' => $reportData,
            'message' => 'Emergency report generated successfully'
        ]);
    }

    /**
     * Generate surgery report.
     */
    public function generateSurgeryReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'surgery_type' => 'nullable|in:major,minor,diagnostic,therapeutic',
            'surgeon_id' => 'nullable|exists:users,id',
            'format' => 'nullable|in:pdf,excel,json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = SurgerySchedule::with(['patient', 'surgeon', 'theatre', 'procedure'])
            ->whereBetween('surgery_date', [$request->date_from, $request->date_to]);

        if ($request->has('surgery_type')) {
            $query->where('surgery_type', $request->surgery_type);
        }

        if ($request->has('surgeon_id')) {
            $query->where('surgeon_id', $request->surgeon_id);
        }

        $surgeries = $query->get();

        $reportData = [
            'title' => 'Surgery Report',
            'period' => "{$request->date_from} to {$request->date_to}",
            'total_surgeries' => $surgeries->count(),
            'surgery_types' => $this->getSurgeryTypeStatistics($surgeries),
            'anesthesia_types' => $this->getAnesthesiaTypeStatistics($surgeries),
            'top_procedures' => $this->getTopProcedures($surgeries),
            'average_duration' => $this->getAverageSurgeryDuration($surgeries),
            'surgeries' => $surgeries
        ];

        if ($request->format === 'pdf') {
            return $this->generatePDFReport($reportData, 'surgery-report');
        } elseif ($request->format === 'excel') {
            return $this->generateExcelReport($reportData, 'surgery-report');
        }

        return response()->json([
            'success' => true,
            'data' => $reportData,
            'message' => 'Surgery report generated successfully'
        ]);
    }

    /**
     * Generate dashboard statistics.
     */
    public function getDashboardStatistics(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30);
        $dateTo = $request->date_to ?? now();

        $stats = [
            'patients' => [
                'total' => Patient::count(),
                'new_this_period' => Patient::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'active' => Patient::whereHas('consultations', function($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('created_at', [$dateFrom, $dateTo]);
                })->count()
            ],
            'appointments' => [
                'total_today' => Appointment::whereDate('appointment_date', today())->count(),
                'completed_today' => Appointment::whereDate('appointment_date', today())->where('status', 'completed')->count(),
                'upcoming' => Appointment::whereDate('appointment_date', '>', today())->whereIn('status', ['scheduled', 'confirmed'])->count()
            ],
            'consultations' => [
                'total_today' => Consultation::whereDate('consultation_date', today())->count(),
                'ongoing' => Consultation::where('consultation_status', 'ongoing')->count(),
                'completed_today' => Consultation::whereDate('consultation_date', today())->where('consultation_status', 'completed')->count()
            ],
            'lab_requests' => [
                'pending' => LabRequest::where('status', 'pending')->count(),
                'completed_today' => LabRequest::whereDate('created_at', today())->where('status', 'completed')->count(),
                'critical_results' => LabResult::where('abnormal_flag', true)->whereDate('created_at', today())->count()
            ],
            'emergency' => [
                'active_visits' => EmergencyVisit::where('status', 'active')->count(),
                'visits_today' => EmergencyVisit::whereDate('arrival_time', today())->count(),
                'critical_cases' => EmergencyVisit::where('priority', 'critical')->whereDate('arrival_time', today())->count()
            ],
            'surgery' => [
                'scheduled_today' => SurgerySchedule::whereDate('surgery_date', today())->count(),
                'in_progress' => SurgerySchedule::where('status', 'in_progress')->count(),
                'completed_today' => SurgerySchedule::whereDate('surgery_date', today())->where('status', 'completed')->count()
            ],
            'financial' => [
                'revenue_today' => Invoice::whereDate('invoice_date', today())->sum('total_amount'),
                'revenue_this_period' => Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])->sum('total_amount'),
                'pending_payments' => Invoice::where('status', 'pending')->sum('total_amount')
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Dashboard statistics retrieved successfully'
        ]);
    }

    /**
     * Generate PDF report.
     */
    private function generatePDFReport($data, $reportType)
    {
        $pdf = Pdf::loadView("reports.{$reportType}", $data);
        $pdf->setPaper('A4', 'portrait');
        
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $reportType . '-' . now()->format('Y-m-d') . '.pdf"'
        ]);
    }

    /**
     * Generate Excel report.
     */
    private function generateExcelReport($data, $reportType)
    {
        // This would integrate with Laravel Excel package
        // For now, return JSON data
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Excel report would be generated here'
        ]);
    }

    /**
     * Compile patient report data.
     */
    private function compilePatientReportData($patient, $reportType, $dateFrom, $dateTo)
    {
        $data = [
            'patient' => $patient,
            'report_type' => $reportType,
            'period' => "{$dateFrom} to {$dateTo}",
            'generated_at' => now()
        ];

        switch ($reportType) {
            case 'summary':
                $data['consultations'] = $patient->consultations()->whereBetween('consultation_date', [$dateFrom, $dateTo])->get();
                $data['lab_requests'] = $patient->labRequests()->whereBetween('created_at', [$dateFrom, $dateTo])->get();
                $data['prescriptions'] = $patient->prescriptions()->whereBetween('prescription_date', [$dateFrom, $dateTo])->get();
                break;
            
            case 'medical_history':
                $data['consultations'] = $patient->consultations()->orderBy('consultation_date', 'desc')->get();
                $data['emergency_visits'] = $patient->emergencyVisits()->orderBy('arrival_time', 'desc')->get();
                break;
            
            case 'lab_results':
                $data['lab_results'] = $patient->labRequests()->with('results')->whereBetween('created_at', [$dateFrom, $dateTo])->get();
                break;
        }

        return $data;
    }

    /**
     * Compile financial report data.
     */
    private function compileFinancialReportData($reportType, $dateFrom, $dateTo)
    {
        $data = [
            'title' => ucfirst(str_replace('_', ' ', $reportType)) . ' Report',
            'period' => "{$dateFrom} to {$dateTo}",
            'generated_at' => now()
        ];

        switch ($reportType) {
            case 'revenue':
                $data['invoices'] = Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])->get();
                $data['total_revenue'] = $data['invoices']->sum('total_amount');
                $data['payment_methods'] = $this->getPaymentMethodStatistics($data['invoices']);
                break;
            
            case 'payment_methods':
                $data['payment_methods'] = $this->getPaymentMethodStatistics(Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])->get());
                break;
        }

        return $data;
    }

    /**
     * Compile NHIS report data.
     */
    private function compileNHISReportData($startDate, $endDate)
    {
        return [
            'title' => 'NHIS Monthly Report',
            'month' => $startDate->format('F Y'),
            'generated_at' => now(),
            'nhis_claims' => $this->getNHISClaims($startDate, $endDate),
            'coverage_statistics' => $this->getCoverageStatistics($startDate, $endDate),
            'claim_statistics' => $this->getClaimStatistics($startDate, $endDate)
        ];
    }

    /**
     * Compile GHS report data.
     */
    private function compileGHSReportData($reportType, $startDate, $endDate)
    {
        $data = [
            'title' => 'GHS ' . ucfirst($reportType) . ' Report',
            'month' => $startDate->format('F Y'),
            'generated_at' => now(),
            'report_type' => $reportType
        ];

        switch ($reportType) {
            case 'malaria':
                $data['malaria_cases'] = $this->getMalariaCases($startDate, $endDate);
                break;
            case 'tb':
                $data['tb_cases'] = $this->getTBCases($startDate, $endDate);
                break;
            case 'hiv':
                $data['hiv_cases'] = $this->getHIVCases($startDate, $endDate);
                break;
        }

        return $data;
    }

    /**
     * Get test type statistics.
     */
    private function getTestTypeStatistics($labRequests)
    {
        return $labRequests->groupBy('test_type')
            ->map(function($requests) {
                return [
                    'count' => $requests->count(),
                    'completed' => $requests->where('status', 'completed')->count(),
                    'pending' => $requests->where('status', 'pending')->count()
                ];
            });
    }

    /**
     * Get abnormal results.
     */
    private function getAbnormalResults($labRequests)
    {
        return $labRequests->flatMap(function($request) {
            return $request->results->where('abnormal_flag', true);
        });
    }

    /**
     * Get triage level statistics.
     */
    private function getTriageLevelStatistics($visits)
    {
        return $visits->groupBy('triage_level')
            ->map(function($visits) {
                return $visits->count();
            });
    }

    /**
     * Get arrival mode statistics.
     */
    private function getArrivalModeStatistics($visits)
    {
        return $visits->groupBy('arrival_mode')
            ->map(function($visits) {
                return $visits->count();
            });
    }

    /**
     * Get top complaints.
     */
    private function getTopComplaints($visits)
    {
        return $visits->groupBy('chief_complaint')
            ->map(function($visits) {
                return $visits->count();
            })
            ->sortDesc()
            ->take(10);
    }

    /**
     * Get average wait time.
     */
    private function getAverageWaitTime($visits)
    {
        $completedVisits = $visits->where('status', 'discharged');
        
        if ($completedVisits->isEmpty()) {
            return 0;
        }

        $totalMinutes = $completedVisits->sum(function($visit) {
            return Carbon::parse($visit->arrival_time)->diffInMinutes(Carbon::parse($visit->discharge_time));
        });

        return round($totalMinutes / $completedVisits->count(), 2);
    }

    /**
     * Get surgery type statistics.
     */
    private function getSurgeryTypeStatistics($surgeries)
    {
        return $surgeries->groupBy('surgery_type')
            ->map(function($surgeries) {
                return $surgeries->count();
            });
    }

    /**
     * Get anesthesia type statistics.
     */
    private function getAnesthesiaTypeStatistics($surgeries)
    {
        return $surgeries->groupBy('anesthesia_type')
            ->map(function($surgeries) {
                return $surgeries->count();
            });
    }

    /**
     * Get top procedures.
     */
    private function getTopProcedures($surgeries)
    {
        return $surgeries->groupBy('procedure.name')
            ->map(function($surgeries) {
                return $surgeries->count();
            })
            ->sortDesc()
            ->take(10);
    }

    /**
     * Get average surgery duration.
     */
    private function getAverageSurgeryDuration($surgeries)
    {
        $completedSurgeries = $surgeries->where('status', 'completed');
        
        if ($completedSurgeries->isEmpty()) {
            return 0;
        }

        $totalMinutes = $completedSurgeries->sum('estimated_duration');
        return round($totalMinutes / $completedSurgeries->count(), 2);
    }

    /**
     * Get payment method statistics.
     */
    private function getPaymentMethodStatistics($invoices)
    {
        return $invoices->groupBy('payment_method')
            ->map(function($invoices) {
                return [
                    'count' => $invoices->count(),
                    'total_amount' => $invoices->sum('total_amount')
                ];
            });
    }

    /**
     * Get NHIS claims.
     */
    private function getNHISClaims($startDate, $endDate)
    {
        return NhisClaim::with(['patient', 'branch'])
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->orderBy('visit_date')
            ->get()
            ->map(fn ($claim) => [
                'claim_id' => $claim->claim_id,
                'patient' => $claim->patient?->full_name,
                'nhis_number' => $claim->nhis_number,
                'visit_date' => $claim->visit_date?->format('Y-m-d'),
                'claimed_amount' => $claim->claimed_amount,
                'approved_amount' => $claim->approved_amount,
                'status' => $claim->status,
            ])
            ->values()
            ->all();
    }

    /**
     * Get coverage statistics.
     */
    private function getCoverageStatistics($startDate, $endDate)
    {
        $claims = NhisClaim::whereBetween('visit_date', [$startDate, $endDate])->get();

        return [
            'total_claims' => $claims->count(),
            'approved' => $claims->where('status', 'approved')->count(),
            'pending' => $claims->whereIn('status', ['pending', 'submitted', 'under_review'])->count(),
            'rejected' => $claims->where('status', 'rejected')->count(),
            'total_claimed' => round((float) $claims->sum('claimed_amount'), 2),
            'total_approved' => round((float) $claims->sum('approved_amount'), 2),
        ];
    }

    /**
     * Get claim statistics.
     */
    private function getClaimStatistics($startDate, $endDate)
    {
        $claims = NhisClaim::whereBetween('visit_date', [$startDate, $endDate])->get();

        return [
            'by_status' => $claims->groupBy('status')->map->count(),
            'average_claim_amount' => $claims->count() > 0
                ? round((float) $claims->avg('claimed_amount'), 2)
                : 0,
            'approval_rate' => $claims->count() > 0
                ? round($claims->where('status', 'approved')->count() / $claims->count() * 100, 1)
                : 0,
        ];
    }

    /**
     * Get malaria cases.
     */
    private function getMalariaCases($startDate, $endDate)
    {
        $report = GhsReport::where('report_type', 'malaria')
            ->whereBetween('period_start_date', [$startDate, $endDate])
            ->latest('period_end_date')
            ->first();

        if ($report) {
            return [
                'source' => 'ghs_report',
                'cases' => $report->malaria_cases,
                'deaths' => $report->malaria_deaths,
                'period' => $report->period_start_date?->format('Y-m-d') . ' to ' . $report->period_end_date?->format('Y-m-d'),
            ];
        }

        return LabRequest::whereBetween('created_at', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('test_type', 'like', '%malaria%')
                    ->orWhere('test_type', 'like', '%Malaria%');
            })
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Get TB cases.
     */
    private function getTBCases($startDate, $endDate)
    {
        $report = GhsReport::where('report_type', 'tb')
            ->whereBetween('period_start_date', [$startDate, $endDate])
            ->latest('period_end_date')
            ->first();

        if ($report) {
            return [
                'source' => 'ghs_report',
                'cases' => $report->tb_cases,
                'deaths' => $report->tb_deaths,
            ];
        }

        return LabRequest::whereBetween('created_at', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('test_type', 'like', '%tb%')
                    ->orWhere('test_type', 'like', '%tuberculosis%');
            })
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Get HIV cases.
     */
    private function getHIVCases($startDate, $endDate)
    {
        $report = GhsReport::where('report_type', 'hiv')
            ->whereBetween('period_start_date', [$startDate, $endDate])
            ->latest('period_end_date')
            ->first();

        if ($report) {
            return [
                'source' => 'ghs_report',
                'cases' => $report->hiv_cases,
                'deaths' => $report->hiv_deaths,
            ];
        }

        return LabRequest::whereBetween('created_at', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('test_type', 'like', '%hiv%')
                    ->orWhere('test_type', 'like', '%HIV%');
            })
            ->where('status', 'completed')
            ->count();
    }
}
