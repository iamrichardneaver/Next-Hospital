<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesUserBranch;
use App\Models\NhisClaim;

class NhisClaimWebController extends Controller
{
    use ResolvesUserBranch;

    public function index()
    {
        $branchId = auth()->user()->hasRole('super_admin')
            ? request('branch_id')
            : $this->resolveUserBranchId(['view_reports', 'generate_reports']);

        $claims = NhisClaim::with(['patient'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('visit_date')
            ->paginate(20);

        return view('reports.nhis-index', compact('claims', 'branchId'));
    }
}
