<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EyeService;

class EyeServiceController extends Controller
{
    public function index()
    {
        $services = EyeService::query()
            ->orderBy('service_name')
            ->paginate(20);

        $statistics = [
            'total' => EyeService::count(),
            'active' => EyeService::where('is_active', true)->count(),
            'nhis_covered' => EyeService::where('nhis_covered', true)->count(),
        ];

        return view('eye-services.index', compact('services', 'statistics'));
    }

    public function show(EyeService $eyeService)
    {
        return view('eye-services.show', compact('eyeService'));
    }
}
