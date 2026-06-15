@extends('layouts.app')

@section('title', 'Vital Signs Records')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Vital Signs Records</h1>
                <p class="page-subtitle">View and manage all recorded vital signs</p>
            </div>
            <div class="page-actions d-flex gap-2">
                @include('components.export-dropdown', [
                    'exportRoute' => route('vitals.export'),
                    'permission' => 'view_vitals',
                    'params' => request()->only(['patient_id', 'start_date', 'end_date', 'recorded_by', 'search']),
                ])
                @can('record_vitals')
                <a href="{{ route('vitals.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Record New Vitals
                </a>
                @endcan
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('vitals.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search Patient</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Patient name...">
                </div>
                <div class="col-md-3">
                    <label for="patient_id" class="form-label">Filter by Patient</label>
                    <select class="form-select" id="patient_id" name="patient_id">
                        <option value="">All Patients</option>
                        @foreach($patients as $patient)
                        <option value="{{ $patient->id }}" {{ request('patient_id') == $patient->id ? 'selected' : '' }}>
                            {{ $patient->full_name }} ({{ $patient->patient_number }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('vitals.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Vitals Table -->
    <div class="card">
        <div class="card-body">
            @if($vitals->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Recorded Date</th>
                            <th>Patient</th>
                            <th>Blood Pressure</th>
                            <th>Pulse</th>
                            <th>Temperature</th>
                            <th>Weight</th>
                            <th>BMI</th>
                            <th>Recorded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vitals as $vital)
                        <tr>
                            <td>
                                {{ $vital->recorded_at ? $vital->recorded_at->format('M d, Y h:i A') : 'N/A' }}
                            </td>
                            <td>
                                @php
                                    // Try to get patient through consultation (null-safe access)
                                    $patient = null;
                                    if ($vital->consultation && $vital->consultation->patient) {
                                        $patient = $vital->consultation->patient;
                                    }
                                @endphp
                                @if($patient)
                                    <a href="{{ route('patients.show', $patient) }}">
                                        {{ $patient->full_name }}
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ $patient->patient_number }}</small>
                                @else
                                    <span class="text-muted" title="No consultation linked">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($vital->blood_pressure_systolic && $vital->blood_pressure_diastolic)
                                    {{ $vital->blood_pressure_systolic }}/{{ $vital->blood_pressure_diastolic }} mmHg
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                {{ $vital->pulse_rate ? $vital->pulse_rate . ' bpm' : '-' }}
                            </td>
                            <td>
                                {{ $vital->temperature ? number_format($vital->temperature, 1) . '°C' : '-' }}
                            </td>
                            <td>
                                {{ $vital->weight ? number_format($vital->weight, 1) . ' kg' : '-' }}
                            </td>
                            <td>
                                {{ $vital->bmi ? number_format($vital->bmi, 1) : '-' }}
                            </td>
                            <td>
                                {{ $vital->recordedBy ? $vital->recordedBy->name : 'N/A' }}
                            </td>
                            <td>
                                <a href="{{ route('vitals.show', $vital) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                @can('record_vitals')
                                <a href="{{ route('vitals.edit', $vital) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $vitals->links() }}
            </div>
            @else
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                No vital signs records found.
                @can('record_vitals')
                <a href="{{ route('vitals.create') }}" class="alert-link">Record your first vital signs</a>
                @endcan
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
