<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\GhsReport;

class GhsReportWebController extends Controller
{
    use ResolvesUserBranch;

    public function index()
    {
        $branchId = auth()->user()->hasRole('super_admin')
            ? request('branch_id')
            : $this->resolveUserBranchId(['view_reports', 'generate_reports']);

        $reports = GhsReport::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('reporting_year')
            ->latest('reporting_month')
            ->paginate(20);

        return view('reports.ghs-index', compact('reports', 'branchId'));
    }
}
