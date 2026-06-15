<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LoginAudit;
use App\Models\User;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'activity');

        $activityQuery = ActivityLog::query()->latest('created_at');
        $loginQuery = LoginAudit::with('user')->latest('logged_at');

        if ($request->filled('date_from')) {
            $activityQuery->whereDate('created_at', '>=', $request->date_from);
            $loginQuery->whereDate('logged_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $activityQuery->whereDate('created_at', '<=', $request->date_to);
            $loginQuery->whereDate('logged_at', '<=', $request->date_to);
        }

        if ($request->filled('user_id')) {
            $userId = $request->user_id;
            $activityQuery->where('causer_type', User::class)->where('causer_id', $userId);
            $loginQuery->where('user_id', $userId);
        }

        if ($request->filled('action')) {
            if ($tab === 'login') {
                $loginQuery->where('action', $request->action);
            } else {
                $activityQuery->where(function ($q) use ($request) {
                    $q->where('event', $request->action)
                        ->orWhere('log_name', $request->action);
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $activityQuery->where('description', 'like', "%{$search}%");
            $loginQuery->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $activityLogs = $tab === 'activity'
            ? $activityQuery->paginate(25, ['*'], 'activity_page')->withQueryString()
            : ActivityLog::whereRaw('1=0')->paginate(25, ['*'], 'activity_page');

        $loginAudits = $tab === 'login'
            ? $loginQuery->paginate(25, ['*'], 'login_page')->withQueryString()
            : LoginAudit::whereRaw('1=0')->paginate(25, ['*'], 'login_page');

        $users = User::orderBy('first_name')->limit(200)->get(['id', 'first_name', 'last_name', 'email']);

        $statistics = [
            'activity_total' => ActivityLog::count(),
            'login_total' => LoginAudit::count(),
            'login_failed' => LoginAudit::where('status', 'failed')->count(),
            'today_activity' => ActivityLog::whereDate('created_at', today())->count(),
        ];

        return view('audit.index', compact(
            'tab',
            'activityLogs',
            'loginAudits',
            'users',
            'statistics'
        ));
    }
}
