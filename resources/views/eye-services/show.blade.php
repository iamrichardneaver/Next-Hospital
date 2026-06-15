@extends('layouts.app')

@section('title', $eyeService->service_name)

@section('content')
<div class="container-fluid py-4">
    <div class="mb-3">
        <a href="{{ route('eye-services.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <div class="card">
        <div class="card-body">
            <h1 class="h4">{{ $eyeService->service_name }}</h1>
            <p class="text-muted">{{ $eyeService->service_code }} · {{ $eyeService->category }}</p>
            <hr>
            <p>{{ $eyeService->description ?: 'No description provided.' }}</p>
            <dl class="row mb-0">
                <dt class="col-sm-3">Base Price</dt><dd class="col-sm-9">{{ $eyeService->currency ?? 'GHS' }} {{ number_format($eyeService->base_price ?? 0, 2) }}</dd>
                <dt class="col-sm-3">NHIS Price</dt><dd class="col-sm-9">{{ $eyeService->nhis_covered ? number_format($eyeService->nhis_price ?? 0, 2) : 'Not covered' }}</dd>
                <dt class="col-sm-3">Duration</dt><dd class="col-sm-9">{{ $eyeService->duration_minutes ? $eyeService->duration_minutes . ' min' : 'N/A' }}</dd>
                <dt class="col-sm-3">Requires Doctor</dt><dd class="col-sm-9">{{ $eyeService->requires_doctor ? 'Yes' : 'No' }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection
