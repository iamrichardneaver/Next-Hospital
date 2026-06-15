@extends('layouts.app')

@section('title', 'Insurance Policies')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1" style="color: #1e3a5f;">Insurance Policies</h1><p class="text-secondary mb-0">Manage patient insurance policies</p></div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPolicyModal"><i class="bi bi-plus-circle"></i> Add Policy</button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Policy #</th><th>Patient</th><th>Provider</th><th>Coverage</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        @foreach($policies as $policy)
                        <tr>
                            <td><strong>{{ $policy->policy_number }}</strong></td>
                            <td>{{ $policy->patient->full_name }}</td>
                            <td>{{ $policy->insuranceProvider->name ?? 'N/A' }}</td>
                            <td>{{ $policy->coverage_type }}</td>
                            <td>{{ \Carbon\Carbon::parse($policy->start_date)->format('M d, Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($policy->end_date)->format('M d, Y') }}</td>
                            <td><span class="badge bg-{{ $policy->is_active ? 'success' : 'secondary' }}">{{ $policy->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td><a href="{{ route('insurance.policies.edit', $policy) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i> Edit</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $policies->links() }}
        </div>
    </div>
</div>

<!-- Add Policy Modal -->
<div class="modal fade" id="addPolicyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Insurance Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('insurance.policies.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Patient <span class="text-danger">*</span></label>
                        <select class="form-select" name="patient_id" required>
                            <option value="">Select Patient</option>
                            @foreach($patients as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->patient_number }} - {{ $patient->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Insurance Provider <span class="text-danger">*</span></label>
                        <select class="form-select" name="provider_id" required>
                            <option value="">Select Provider</option>
                            @foreach($providers as $provider)
                            <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Policy Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="policy_number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Coverage Type</label>
                        <select class="form-select" name="coverage_type">
                            <option value="Full">Full Coverage</option>
                            <option value="Partial">Partial Coverage</option>
                            <option value="Basic">Basic Coverage</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Policy</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
