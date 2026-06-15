@extends('layouts.app')

@section('title', 'Edit Insurance Policy')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Edit Policy {{ $policy->policy_number }}</h1>
    <div class="col-lg-8"><div class="card shadow-sm"><div class="card-body">
        <form action="{{ route('insurance.policies.update', $policy) }}" method="POST">@csrf @method('PUT')
            <div class="mb-3"><label class="form-label">Patient *</label>
                <select name="patient_id" class="form-select" required>@foreach($patients as $p)<option value="{{ $p->id }}" @selected(old('patient_id', $policy->patient_id)==$p->id)>{{ $p->full_name }}</option>@endforeach</select>
            </div>
            <div class="mb-3"><label class="form-label">Provider *</label>
                <select name="insurance_provider_id" class="form-select" required>@foreach($providers as $pr)<option value="{{ $pr->id }}" @selected(old('insurance_provider_id', $policy->insurance_provider_id)==$pr->id)>{{ $pr->name }}</option>@endforeach</select>
            </div>
            <div class="mb-3"><label class="form-label">Policy Number *</label><input name="policy_number" class="form-control" value="{{ old('policy_number', $policy->policy_number) }}" required></div>
            <div class="mb-3"><label class="form-label">Coverage Type *</label><input name="coverage_type" class="form-control" value="{{ old('coverage_type', $policy->coverage_type) }}" required></div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Start Date *</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date', optional($policy->start_date)->format('Y-m-d')) }}" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">End Date *</label><input type="date" name="end_date" class="form-control" value="{{ old('end_date', optional($policy->end_date)->format('Y-m-d')) }}" required></div>
            </div>
            <div class="mb-3"><label class="form-label">Coverage %</label><input type="number" name="coverage_percentage" class="form-control" value="{{ old('coverage_percentage', $policy->coverage_percentage) }}"></div>
            <div class="form-check mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active" @checked(old('is_active', $policy->is_active))><label class="form-check-label" for="active">Active</label></div>
            <button class="btn btn-primary">Update Policy</button>
            <a href="{{ route('insurance.policies') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div></div></div>
</div>
@endsection
