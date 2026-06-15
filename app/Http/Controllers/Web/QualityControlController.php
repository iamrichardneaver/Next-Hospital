<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LabQualityControl;
use App\Models\LabTestTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QualityControlController extends Controller
{
    /**
     * Display a listing of quality control records
     */
    public function index(Request $request)
    {
        $query = LabQualityControl::with(['performedBy', 'parameter']);
        
        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('lot_number', 'like', "%{$search}%")
                  ->orWhere('qc_material', 'like', "%{$search}%")
                  ->orWhere('qc_level', 'like', "%{$search}%");
            });
        }
        
        // Date filter
        if ($request->filled('date_from')) {
            $query->whereDate('performed_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('performed_at', '<=', $request->date_to);
        }
        
        // Status filter
        if ($request->filled('status')) {
            $query->where('is_acceptable', $request->status === 'acceptable');
        }
        
        $records = $query->latest('performed_at')->paginate(20);
        
        // Statistics
        $statistics = [
            'total' => LabQualityControl::count(),
            'this_month' => LabQualityControl::whereMonth('performed_at', now()->month)->count(),
            'passed' => LabQualityControl::where('is_acceptable', true)->whereMonth('performed_at', now()->month)->count(),
            'failed' => LabQualityControl::where('is_acceptable', false)->whereMonth('performed_at', now()->month)->count(),
        ];
        
        return view('lab.quality-control.index', compact('records', 'statistics'));
    }

    /**
     * Show the form for creating a new quality control record
     */
    public function create()
    {
        $parameters = \App\Models\LabTestParameter::with('template')
            ->whereHas('template', function($q) {
                $q->where('is_active', true);
            })
            ->orderBy('parameter_name')
            ->get();
        return view('lab.quality-control.create', compact('parameters'));
    }

    /**
     * Store a newly created quality control record
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'parameter_id' => 'required|exists:lab_test_parameters,id',
                'qc_type' => 'required|in:internal,external',
                'qc_level' => 'required|in:level_1,level_2,level_3',
                'qc_material' => 'required|string|max:255',
                'lot_number' => 'required|string|max:100',
                'expiry_date' => 'required|date',
                'target_value' => 'required|numeric',
                'measured_value' => 'required|numeric',
                'acceptable_range_low' => 'required|numeric',
                'acceptable_range_high' => 'required|numeric',
                'notes' => 'nullable|string',
            ]);
            
            // Determine if result is acceptable
            $measuredValue = floatval($validated['measured_value']);
            $lowRange = floatval($validated['acceptable_range_low']);
            $highRange = floatval($validated['acceptable_range_high']);
            
            $validated['is_acceptable'] = ($measuredValue >= $lowRange && $measuredValue <= $highRange);
            $validated['performed_by'] = auth()->id();
            $validated['performed_at'] = now();
            
            LabQualityControl::create($validated);
            
            return redirect()->route('lab.quality-control.index')
                ->with('success', 'Quality control record created successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating quality control record: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to create quality control record. Please try again.');
        }
    }

    /**
     * Display the specified quality control record
     */
    public function show(LabQualityControl $qualityControl)
    {
        $qualityControl->load('performedBy');
        return view('lab.quality-control.show', compact('qualityControl'));
    }

    /**
     * Show the form for editing the specified quality control record
     */
    public function edit(LabQualityControl $qualityControl)
    {
        $parameters = \App\Models\LabTestParameter::with('template')
            ->whereHas('template', function($q) {
                $q->where('is_active', true);
            })
            ->orderBy('parameter_name')
            ->get();
        return view('lab.quality-control.edit', compact('qualityControl', 'parameters'));
    }

    /**
     * Update the specified quality control record
     */
    public function update(Request $request, LabQualityControl $qualityControl)
    {
        try {
            $validated = $request->validate([
                'parameter_id' => 'required|exists:lab_test_parameters,id',
                'qc_type' => 'required|in:internal,external',
                'qc_level' => 'required|in:level_1,level_2,level_3',
                'qc_material' => 'required|string|max:255',
                'lot_number' => 'required|string|max:100',
                'expiry_date' => 'required|date',
                'target_value' => 'required|numeric',
                'measured_value' => 'required|numeric',
                'acceptable_range_low' => 'required|numeric',
                'acceptable_range_high' => 'required|numeric',
                'notes' => 'nullable|string',
            ]);
            
            // Re-determine if result is acceptable
            $measuredValue = floatval($validated['measured_value']);
            $lowRange = floatval($validated['acceptable_range_low']);
            $highRange = floatval($validated['acceptable_range_high']);
            
            $validated['is_acceptable'] = ($measuredValue >= $lowRange && $measuredValue <= $highRange);
            
            $qualityControl->update($validated);
            
            return redirect()->route('lab.quality-control.index')
                ->with('success', 'Quality control record updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating quality control record: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'quality_control_id' => $qualityControl->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update quality control record. Please try again.');
        }
    }

    /**
     * Remove the specified quality control record
     */
    public function destroy(LabQualityControl $qualityControl)
    {
        try {
            $qualityControl->delete();
            
            return redirect()->route('lab.quality-control.index')
                ->with('success', 'Quality control record deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting quality control record: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'quality_control_id' => $qualityControl->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Failed to delete quality control record. Please try again.');
        }
    }

    /**
     * Display QC statistics and trends
     */
    public function statistics(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        
        // Get QC records for the date range
        $records = LabQualityControl::with('parameter')
            ->whereBetween('performed_at', [$dateFrom, $dateTo])
            ->orderBy('performed_at')
            ->get();
        
        // Group by parameter for analysis
        $parameterStats = $records->groupBy('parameter_id')->map(function($paramRecords) {
            $passed = $paramRecords->where('is_acceptable', true)->count();
            $failed = $paramRecords->where('is_acceptable', false)->count();
            $total = $paramRecords->count();
            
            return [
                'parameter' => $paramRecords->first()->parameter,
                'total' => $total,
                'passed' => $passed,
                'failed' => $failed,
                'pass_rate' => $total > 0 ? round(($passed / $total) * 100, 1) : 0,
                'latest' => $paramRecords->sortByDesc('performed_at')->first()
            ];
        });
        
        return view('lab.quality-control.statistics', compact('parameterStats', 'dateFrom', 'dateTo'));
    }
}
