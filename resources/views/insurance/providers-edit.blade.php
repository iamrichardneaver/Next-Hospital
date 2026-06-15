@extends('layouts.app')

@section('title', 'Edit Insurance Provider')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Edit Provider: {{ $provider->name }}</h1>
    <div class="col-lg-8"><div class="card shadow-sm"><div class="card-body">
        <form action="{{ route('insurance.providers.update', $provider) }}" method="POST">@csrf @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Name *</label><input name="name" class="form-control" value="{{ old('name', $provider->name) }}" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Code *</label><input name="code" class="form-control" value="{{ old('code', $provider->code) }}" required></div>
            </div>
            <div class="mb-3"><label class="form-label">Type *</label>
                <select name="type" class="form-select" required>@foreach(['private','public','corporate','government'] as $t)<option value="{{ $t }}" @selected(old('type', $provider->type)===$t)>{{ ucfirst($t) }}</option>@endforeach</select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Contact Person</label><input name="contact_person" class="form-control" value="{{ old('contact_person', $provider->contact_person) }}"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $provider->phone) }}"></div>
            </div>
            <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $provider->email) }}"></div>
            <div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2">{{ old('address', $provider->address) }}</textarea></div>
            <div class="mb-3"><label class="form-label">Website</label><input type="url" name="website" class="form-control" value="{{ old('website', $provider->website) }}"></div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Default Coverage %</label><input type="number" name="default_coverage_percentage" class="form-control" value="{{ old('default_coverage_percentage', $provider->default_coverage_percentage) }}"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Default Co-pay %</label><input type="number" name="default_co_pay_percentage" class="form-control" value="{{ old('default_co_pay_percentage', $provider->default_co_pay_percentage) }}"></div>
            </div>
            <div class="form-check mb-2"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active" @checked(old('is_active', $provider->is_active))><label class="form-check-label" for="active">Active</label></div>
            <button class="btn btn-primary">Update Provider</button>
            <a href="{{ route('insurance.providers') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div></div></div>
</div>
@endsection
