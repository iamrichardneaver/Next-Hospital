@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4"><i class="bi bi-journal-text me-2"></i>Audit Trail</h1>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Activity Logs</small><h4>{{ $statistics['activity_total'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Login Events</small><h4>{{ $statistics['login_total'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Failed Logins</small><h4>{{ $statistics['login_failed'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Today's Activity</small><h4>{{ $statistics['today_activity'] }}</h4></div></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="col-md-2"><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
                <div class="col-md-2"><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
                <div class="col-md-2"><label class="form-label">User</label>
                    <select name="user_id" class="form-select"><option value="">All</option>
                        @foreach($users as $user)<option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->first_name }} {{ $user->last_name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Action</label><input type="text" name="action" class="form-control" value="{{ request('action') }}" placeholder="event / log name"></div>
                <div class="col-md-2"><label class="form-label">Search</label><input type="text" name="search" class="form-control" value="{{ request('search') }}"></div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
            </form>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link {{ $tab === 'activity' ? 'active' : '' }}" href="?tab=activity">Activity Logs</a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'login' ? 'active' : '' }}" href="?tab=login">Login Audit</a></li>
    </ul>

    @if($tab === 'login')
    <div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Time</th><th>User</th><th>Email</th><th>Action</th><th>Status</th><th>IP</th></tr></thead><tbody>
        @forelse($loginAudits as $audit)
            <tr>
                <td>{{ optional($audit->logged_at ?? $audit->created_at)->format('Y-m-d H:i') }}</td>
                <td>{{ $audit->user?->name ?? '—' }}</td>
                <td>{{ $audit->email ?? '—' }}</td>
                <td>{{ $audit->action ?? 'login' }}</td>
                <td><span class="badge bg-{{ $audit->status === 'failed' ? 'danger' : 'success' }}">{{ ucfirst($audit->status ?? 'success') }}</span></td>
                <td>{{ $audit->ip_address ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No login audit records.</td></tr>
        @endforelse
    </tbody></table></div>@if($loginAudits->hasPages())<div class="card-footer">{{ $loginAudits->links() }}</div>@endif</div>
    @else
    <div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Time</th><th>Log</th><th>Event</th><th>Description</th><th>User</th></tr></thead><tbody>
        @forelse($activityLogs as $log)
            <tr>
                <td>{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                <td>{{ $log->log_name }}</td>
                <td>{{ $log->event ?? '—' }}</td>
                <td>{{ Str::limit($log->description, 80) }}</td>
                <td>{{ $log->getCauserName() }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No activity logs found.</td></tr>
        @endforelse
    </tbody></table></div>@if($activityLogs->hasPages())<div class="card-footer">{{ $activityLogs->links() }}</div>@endif</div>
    @endif
</div>
@endsection
