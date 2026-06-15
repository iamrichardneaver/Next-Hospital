<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ReportCatalog;

class ReportsHubController extends Controller
{
    public function index()
    {
        $reportGroups = ReportCatalog::accessibleGroupedFor(auth()->user());

        return view('reports.index', compact('reportGroups'));
    }
}
