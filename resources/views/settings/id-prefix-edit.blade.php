@extends('layouts.app')

@section('title', 'Edit ID Prefix')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-pencil me-2"></i>Edit ID Prefix
            </h1>
            <p class="text-secondary mb-0">
                {{ ucfirst(str_replace('_', ' ', $setting->entity_type)) }}
                <code class="text-muted">({{ $setting->entity_type }})</code>
            </p>
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

    @if($setting->is_locked)
    <div class="alert alert-danger">
        <i class="bi bi-lock-fill me-2"></i>
        <strong>Locked:</strong> This pattern cannot be modified because records already exist for this entity type.
        Current sequence: <strong>{{ number_format($setting->current_sequence) }}</strong>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-hash me-2"></i>Prefix Configuration</h5>
                </div>
                <div class="card-body">
                    @if($setting->is_locked)
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Company Prefix</dt>
                            <dd class="col-sm-8"><code>{{ $setting->company_prefix }}</code></dd>
                            <dt class="col-sm-4">Module Prefix</dt>
                            <dd class="col-sm-8"><code>{{ $setting->module_prefix }}</code></dd>
                            <dt class="col-sm-4">Pattern</dt>
                            <dd class="col-sm-8"><code>{{ $setting->pattern }}</code></dd>
                            <dt class="col-sm-4">Example ID</dt>
                            <dd class="col-sm-8"><span class="badge bg-primary">{{ $setting->formatId() }}</span></dd>
                            <dt class="col-sm-4">Sequence Length</dt>
                            <dd class="col-sm-8">{{ $setting->sequence_length }}</dd>
                            <dt class="col-sm-4">Description</dt>
                            <dd class="col-sm-8">{{ $setting->description ?? '—' }}</dd>
                        </dl>
                    @else
                        <form action="{{ route('id-prefixes.update', $setting->entity_type) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('settings.partials.id-prefix-form', ['setting' => $setting, 'patternExamples' => $patternExamples])

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-warning">
                                    <i class="bi bi-save me-1"></i> Save Changes
                                </button>
                                <a href="{{ route('id-prefixes.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-tag me-2"></i>Current Preview</h5>
                    <p class="mb-1"><strong>Pattern:</strong></p>
                    <p><code>{{ $setting->pattern }}</code></p>
                    <p class="mb-1"><strong>Next ID would be:</strong></p>
                    <p><span class="badge bg-primary fs-6">{{ $setting->formatId() }}</span></p>
                    <p class="mb-1"><strong>Status:</strong></p>
                    <p>
                        @if($setting->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                        @if($setting->is_locked)
                            <span class="badge bg-danger">Locked</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
