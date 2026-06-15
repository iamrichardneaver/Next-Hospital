@extends('layouts.app')

@section('title', 'ICU Patient')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ $icu->patient?->full_name ?? 'ICU Record' }}</h1>
            <p class="text-muted mb-0">Admitted {{ optional($icu->admission_time)->format('Y-m-d H:i') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('icu.index') }}" class="btn btn-secondary">Back</a>
            @can('manage_wards')
            <a href="{{ route('icu.edit', $icu) }}" class="btn btn-warning">Edit</a>
            @if($icu->status === 'active')
            <a href="{{ route('icu.discharge.form', $icu) }}" class="btn btn-success">Discharge</a>
            @endif
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Condition</small><h5><span class="badge bg-{{ $icu->patient_condition === 'critical' ? 'danger' : 'warning' }}">{{ ucfirst($icu->patient_condition) }}</span></h5></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Status</small><h5>{{ ucfirst($icu->status) }}</h5></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Bed</small><h5>{{ $icu->bed?->ward?->name }} / {{ $icu->bed?->bed_number ?? '—' }}</h5></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Ventilator</small><h5>{{ $icu->on_ventilator ? 'Yes' : 'No' }}</h5></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Attending Doctor</dt><dd class="col-sm-9">{{ $icu->attendingDoctor?->name ?? '—' }}</dd>
                <dt class="col-sm-3">Assigned Nurse</dt><dd class="col-sm-9">{{ $icu->assignedNurse?->name ?? '—' }}</dd>
                <dt class="col-sm-3">Admission Type</dt><dd class="col-sm-9">{{ ucfirst($icu->admission_type) }}</dd>
                <dt class="col-sm-3">Diagnosis</dt><dd class="col-sm-9">{{ $icu->admission_diagnosis ?? '—' }}</dd>
                <dt class="col-sm-3">Chief Complaint</dt><dd class="col-sm-9">{{ $icu->chief_complaint ?? '—' }}</dd>
                @if($icu->discharge_time)
                <dt class="col-sm-3">Discharged</dt><dd class="col-sm-9">{{ $icu->discharge_time->format('Y-m-d H:i') }} — {{ $icu->discharge_destination ?? '' }}</dd>
                <dt class="col-sm-3">Discharge Notes</dt><dd class="col-sm-9">{{ $icu->discharge_notes ?? '—' }}</dd>
                @endif
            </dl>
        </div>
    </div>
</div>
@endsection
