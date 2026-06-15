@extends('layouts.app')

@section('title', 'Create ID Prefix')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-plus-circle me-2"></i>Create ID Prefix
            </h1>
            <p class="text-secondary mb-0">Configure a new ID generation pattern for a system entity</p>
        </div>
        <a href="{{ route('id-prefixes.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to ID Prefixes
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-hash me-2"></i>Prefix Configuration</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('id-prefixes.store') }}" method="POST">
                        @csrf
                        @include('settings.partials.id-prefix-form', ['availableTypes' => $availableTypes, 'patternExamples' => $patternExamples])

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Create Prefix
                            </button>
                            <a href="{{ route('id-prefixes.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-info-circle me-2"></i>Pattern Guide</h5>
                    <p class="small text-muted mb-2">IDs are built from your pattern using live date values and an auto-incrementing sequence.</p>
                    <ul class="small mb-0">
                        <li><strong>Patient:</strong> PAT-, HWC/PAT/...</li>
                        <li><strong>Invoice:</strong> INV-, HWC/INV/...</li>
                        <li><strong>Lab:</strong> LAB-, HWC/LAB/...</li>
                        <li><strong>Visit:</strong> VST-, HWC/VST/...</li>
                    </ul>
                    <div class="alert alert-warning mt-3 mb-0 small">
                        <i class="bi bi-lock me-1"></i>
                        Patterns lock automatically once records exist for that entity type.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
