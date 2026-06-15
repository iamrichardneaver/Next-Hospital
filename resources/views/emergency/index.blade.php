@extends('layouts.app')

@section('title', 'Emergency Visits')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-danger"><i class="bi bi-hospital"></i> Emergency Visits</h1>
            <p class="text-secondary mb-0">Emergency department visit management</p>
        </div>
        <div class="d-flex gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('emergency.export'),
                'permission' => 'view_emergency_visits',
            ])
            @can('create_emergency_visits')
            <a href="{{ route('emergency.create') }}" class="btn btn-danger"><i class="bi bi-plus-circle"></i> New Visit</a>
            @endcan
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3"><div class="card"><div class="card-body"><small class="text-muted">Total</small><h4>{{ $statistics['total'] }}</h4></div></div></div>
        <div class="col-md-3 mb-3"><div class="card"><div class="card-body"><small class="text-muted">Active</small><h4>{{ $statistics['active'] }}</h4></div></div></div>
        <div class="col-md-3 mb-3"><div class="card"><div class="card-body"><small class="text-muted">Completed</small><h4>{{ $statistics['completed'] }}</h4></div></div></div>
        <div class="col-md-3 mb-3"><div class="card"><div class="card-body"><small class="text-muted">Critical</small><h4>{{ $statistics['critical'] }}</h4></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Visit #</th>
                            <th>Patient</th>
                            <th>Triage</th>
                            <th>Doctor</th>
                            <th>Nurse</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($emergencyVisits as $visit)
                        <tr>
                            <td><strong>{{ $visit->visit_number }}</strong></td>
                            <td>{{ $visit->patient?->full_name ?? '-' }}</td>
                            <td><span class="badge bg-{{ $visit->triage_level === 'critical' ? 'danger' : ($visit->triage_level === 'urgent' ? 'warning' : 'secondary') }}">{{ ucfirst($visit->triage_level ?? '-') }}</span></td>
                            <td>{{ $visit->assignedDoctor ? 'Dr. ' . $visit->assignedDoctor->first_name . ' ' . $visit->assignedDoctor->last_name : '-' }}</td>
                            <td>{{ $visit->assignedNurse ? $visit->assignedNurse->first_name . ' ' . $visit->assignedNurse->last_name : '-' }}</td>
                            <td>{{ ucfirst($visit->status ?? '-') }}</td>
                            <td>
                                <a href="{{ route('emergency.show', $visit) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No emergency visits found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($emergencyVisits->hasPages())
                <div class="mt-3">{{ $emergencyVisits->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
