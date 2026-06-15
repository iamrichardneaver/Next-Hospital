@extends('layouts.app')

@section('title', 'ICU Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-heart-pulse me-2"></i>ICU Dashboard</h1>
        @can('manage_wards')
        <a href="{{ route('icu.create') }}" class="btn btn-danger"><i class="bi bi-plus-circle"></i> Admit Patient</a>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Active</small><h4>{{ $statistics['active'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Critical</small><h4>{{ $statistics['critical'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">On Ventilator</small><h4>{{ $statistics['on_ventilator'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Total Logs</small><h4>{{ $statistics['total'] }}</h4></div></div></div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Admission</th>
                        <th>Condition</th>
                        <th>Doctor</th>
                        <th>Bed</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->patient?->full_name ?? '-' }}</td>
                            <td>{{ optional($log->admission_time)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td><span class="badge bg-{{ $log->patient_condition === 'critical' ? 'danger' : 'warning' }}">{{ ucfirst($log->patient_condition ?? 'stable') }}</span></td>
                            <td>{{ $log->attendingDoctor?->name ?? '-' }}</td>
                            <td>{{ $log->bed?->bed_number ?? '-' }}</td>
                            <td>{{ ucfirst($log->status ?? '-') }}</td>
                            <td><a href="{{ route('icu.show', $log) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No ICU logs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
