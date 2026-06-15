<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LoginAudit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function activityLogs(Request $request): JsonResponse
    {
        $query = ActivityLog::query()->latest('created_at');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('user_id')) {
            $query->where('causer_type', User::class)->where('causer_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where(function ($q) use ($request) {
                $q->where('event', $request->action)
                    ->orWhere('log_name', $request->action);
            });
        }

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->paginate($request->integer('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function loginAudits(Request $request): JsonResponse
    {
        $query = LoginAudit::with('user:id,first_name,last_name,email')->latest('logged_at');

        if ($request->filled('date_from')) {
            $query->whereDate('logged_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('logged_at', '<=', $request->date_to);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $audits = $query->paginate($request->integer('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $audits,
        ]);
    }
}
