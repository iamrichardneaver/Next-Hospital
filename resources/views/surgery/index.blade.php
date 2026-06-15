@extends('layouts.app')

@section('title', 'Surgery Schedules')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-scissors me-2"></i>Surgery Schedules</h1>
        <div class="d-flex gap-2">
            @include('components.export-dropdown', [
                'exportRoute' => route('surgery.export'),
                'permission' => 'view_surgery_schedules',
            ])
            @can('create_surgery_schedules')
            <a href="{{ route('surgery.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Schedule Surgery</a>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-2"><div class="card"><div class="card-body"><small class="text-muted">Total</small><h4>{{ $statistics['total'] }}</h4></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><small class="text-muted">Today</small><h4>{{ $statistics['today'] }}</h4></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><small class="text-muted">Scheduled</small><h4>{{ $statistics['scheduled'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">In Progress</small><h4>{{ $statistics['in_progress'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Completed</small><h4>{{ $statistics['completed'] }}</h4></div></div></div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Surgery #</th>
                        <th>Patient</th>
                        <th>Procedure</th>
                        <th>Surgeon</th>
                        <th>Theatre</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($surgeries as $surgery)
                        <tr>
                            <td>{{ $surgery->surgery_number ?? '-' }}</td>
                            <td>{{ $surgery->patient?->full_name ?? '-' }}</td>
                            <td>{{ $surgery->procedure?->name ?? '-' }}</td>
                            <td>{{ $surgery->surgeon?->name ?? '-' }}</td>
                            <td>{{ $surgery->theatre?->name ?? '-' }}</td>
                            <td>{{ optional($surgery->surgery_date)->format('Y-m-d') ?? '-' }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $surgery->status ?? 'scheduled')) }}</span></td>
                            <td><a href="{{ route('surgery.show', $surgery) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No surgery schedules found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($surgeries->hasPages())
            <div class="card-footer">{{ $surgeries->links() }}</div>
        @endif
    </div>
</div>
@endsection
