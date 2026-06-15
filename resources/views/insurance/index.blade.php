@extends('layouts.app')

@section('title', 'Insurance Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1" style="color: #1e3a5f;">Insurance Management</h1><p class="text-secondary mb-0">Manage policies and claims</p></div>
        <div>
            <a href="{{ route('insurance.policies') }}" class="btn btn-primary"><i class="bi bi-shield-check"></i> Policies</a>
            <a href="{{ route('insurance.claims') }}" class="btn btn-success"><i class="bi bi-file-earmark-medical"></i> Claims</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body text-center"><h3>{{ $statistics['total_policies'] }}</h3><small>Total Policies</small></div></div></div>
        <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body text-center"><h3>{{ $statistics['active_policies'] }}</h3><small>Active Policies</small></div></div></div>
        <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body text-center"><h3>{{ $statistics['total_claims'] }}</h3><small>Total Claims</small></div></div></div>
        <div class="col-md-3"><div class="card bg-warning text-white"><div class="card-body text-center"><h3>{{ $statistics['pending_claims'] }}</h3><small>Pending Claims</small></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Recent Insurance Policies</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Policy #</th><th>Patient</th><th>Provider</th><th>Coverage</th><th>Valid Until</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        @foreach($policies as $policy)
                        <tr>
                            <td><strong>{{ $policy->policy_number }}</strong></td>
                            <td>{{ $policy->patient->full_name }}<br><small class="text-secondary">{{ $policy->patient->patient_number }}</small></td>
                            <td>{{ $policy->insuranceProvider->name ?? 'N/A' }}</td>
                            <td><span class="badge bg-info">{{ $policy->coverage_type }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($policy->end_date)->format('M d, Y') }}</td>
                            <td>
                                @php
                                    $isActive = $policy->is_active && \Carbon\Carbon::parse($policy->end_date)->isFuture();
                                @endphp
                                <span class="badge bg-{{ $isActive ? 'success' : 'secondary' }}">{{ $isActive ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td><a href="#" class="btn btn-sm btn-info"><i class="bi bi-eye"></i> View</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $policies->links() }}
        </div>
    </div>
</div>
@endsection
