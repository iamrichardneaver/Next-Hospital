<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GhsReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GhsReportController extends Controller
{
    public function index(Request $request)
    {
        $query = GhsReport::with(['preparedBy', 'reviewedBy', 'submittedBy', 'branch']);
        
        if ($request->has('report_type')) {
            $query->byType($request->report_type);
        }
        
        if ($request->has('year')) {
            $query->byYear($request->year);
        }
        
        if ($request->has('month')) {
            $query->byMonth($request->month);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $reports = $query->latest()->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:monthly_disease_surveillance,weekly_disease_surveillance,idsr,maternal_health,child_health,immunization,malaria,tuberculosis,hiv_aids,covid19,births_deaths,quarterly_report,annual_report,other',
            'report_period' => 'required|string',
            'period_start_date' => 'required|date',
            'period_end_date' => 'required|date|after_or_equal:period_start_date',
            'reporting_year' => 'required|integer',
            'reporting_month' => 'nullable|integer|min:1|max:12',
            'reporting_quarter' => 'nullable|integer|min:1|max:4',
            'branch_id' => 'nullable|exists:branches,id',
            'facility_code' => 'nullable|string',
            'district' => 'nullable|string',
            'region' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            $report = GhsReport::create([
                ...$request->all(),
                'prepared_by' => auth()->id(),
                'status' => 'draft',
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'GHS report created successfully',
                'data' => $report->load(['preparedBy', 'branch']),
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create report: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function show($id)
    {
        $report = GhsReport::with([
            'preparedBy',
            'reviewedBy',
            'submittedBy',
            'branch'
        ])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $report = GhsReport::findOrFail($id);
        
        // Only allow updates on draft reports
        if (!in_array($report->status, ['draft', 'pending_review'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update submitted or accepted reports',
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'report_type' => 'sometimes|string',
            'report_period' => 'nullable|string',
            'period_start_date' => 'nullable|date',
            'period_end_date' => 'nullable|date',
            'reporting_month' => 'nullable|integer',
            'reporting_quarter' => 'nullable|integer',
            'reporting_year' => 'nullable|integer',
            'disease_data' => 'nullable|string',
            'total_cases' => 'nullable|integer|min:0',
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
            $report->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Report updated successfully',
                'data' => $report->fresh()->load(['preparedBy']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update report: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function submitReport(Request $request, $id)
    {
        $report = GhsReport::findOrFail($id);
        
        if ($report->status !== 'draft' && $report->status !== 'pending_review') {
            return response()->json([
                'success' => false,
                'message' => 'Report has already been submitted',
            ], 400);
        }
        
        try {
            $report->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'submitted_by' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Report submitted successfully',
                'data' => $report->fresh(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit report: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function reviewReport(Request $request, $id)
    {
        $report = GhsReport::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:reviewed,rejected',
            'comments' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $report->update([
                'status' => $request->status,
                'reviewed_by' => auth()->id(),
                'comments' => $request->comments,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Report reviewed successfully',
                'data' => $report->fresh(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to review report: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function destroy($id)
    {
        try {
            $report = GhsReport::findOrFail($id);
            
            // Only allow deletion of draft reports
            if ($report->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft reports can be deleted',
                ], 403);
            }
            
            $report->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Report deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete report: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function generateAutoReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            // Auto-generate report data from database
            $reportData = $this->compileReportData(
                $request->report_type,
                $request->start_date,
                $request->end_date,
                $request->branch_id
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Report data generated successfully',
                'data' => $reportData,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    private function compileReportData($reportType, $startDate, $endDate, $branchId = null)
    {
        // This would pull data from various tables based on report type
        // For now, returning structure
        
        return [
            'report_type' => $reportType,
            'period_start_date' => $startDate,
            'period_end_date' => $endDate,
            'total_cases' => 0,
            'total_deaths' => 0,
            // Add more fields based on report type
        ];
    }
}

