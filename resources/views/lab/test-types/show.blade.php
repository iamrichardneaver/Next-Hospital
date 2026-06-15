@extends('layouts.app')

@section('title', 'Test Type Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">{{ $testType->test_name }}</h1>
            <p class="text-secondary mb-0">Test Type Details</p>
        </div>
        <div>
            @can('manage_lab_setup')
            <a href="{{ route('lab.test-types.edit', $testType) }}" class="btn btn-primary me-2">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endcan
            <a href="{{ route('lab.test-types') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Test Types
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Test Code:</th>
                                    <td><code>{{ $testType->test_code }}</code></td>
                                </tr>
                                <tr>
                                    <th>Test Name:</th>
                                    <td><strong>{{ $testType->test_name }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Category:</th>
                                    <td><span class="badge bg-info">{{ $testType->category }}</span></td>
                                </tr>
                                <tr>
                                    <th>Subcategory:</th>
                                    <td>{{ $testType->subcategory ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Description:</th>
                                    <td>{{ $testType->description ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Specimen Type:</th>
                                    <td>{{ $testType->specimen_type ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Collection Method:</th>
                                    <td>{{ $testType->collection_method ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Methodology:</th>
                                    <td>{{ $testType->methodology ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Equipment Required:</th>
                                    <td>{{ $testType->equipment_required ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>GHS Code:</th>
                                    <td>{{ $testType->ghs_code ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Result template:</th>
                                    <td>
                                        @if($testType->template)
                                            <a href="{{ route('lab.templates.show', $testType->template) }}">{{ $testType->template->template_name }}</a>
                                            <span class="badge bg-secondary ms-1">{{ $testType->template->template_code }}</span>
                                            <br><small class="text-muted">{{ $testType->template->parameters->count() }} parameters — lab results can be entered</small>
                                        @else
                                            <span class="text-warning">No template assigned</span>
                                            <br><small class="text-muted">Lab scientists cannot enter results until a template is assigned.</small>
                                            @can('manage_lab_setup')
                                            <br><a href="{{ route('lab.test-types.edit', $testType) }}">Assign a template</a>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Turnaround Times</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <h6 class="text-primary">Routine</h6>
                                <h4>{{ $testType->routine_tat_hours ?? 'N/A' }} hours</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <h6 class="text-warning">Urgent</h6>
                                <h4>{{ $testType->urgent_tat_hours ?? 'N/A' }} hours</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <h6 class="text-danger">STAT</h6>
                                <h4>{{ $testType->stat_tat_hours ?? 'N/A' }} hours</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Pricing Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="p-3 border rounded">
                                <h6>Standard Cost</h6>
                                <h4 class="text-primary">GHS {{ number_format($testType->cost ?? 0, 2) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded">
                                <h6>NHIS Cost</h6>
                                <h4 class="text-success">GHS {{ number_format($testType->nhis_cost ?? 0, 2) }}</h4>
                                @if($testType->nhis_covered)
                                    <span class="badge bg-primary">NHIS Covered</span>
                                @else
                                    <span class="badge bg-secondary">Not NHIS Covered</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Status & Requirements</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Status:</strong>
                        <span class="badge bg-{{ $testType->is_active ? 'success' : 'secondary' }} ms-2">
                            {{ $testType->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Requirements:</strong>
                        <div class="mt-2">
                            @if($testType->requires_doctor_approval)
                                <span class="badge bg-warning mb-1">Doctor Approval Required</span><br>
                            @endif
                            @if($testType->requires_consultant_review)
                                <span class="badge bg-info mb-1">Consultant Review Required</span><br>
                            @endif
                            @if($testType->requires_qc)
                                <span class="badge bg-primary mb-1">Quality Control Required</span><br>
                            @endif
                            @if($testType->requires_verification)
                                <span class="badge bg-secondary mb-1">Verification Required</span><br>
                            @endif
                            @if($testType->ghs_mandatory)
                                <span class="badge bg-danger mb-1">GHS Mandatory Reporting</span><br>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Statistics</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Tests Using This Type:</th>
                            <td>{{ $testType->tests->count() }}</td>
                        </tr>
                        <tr>
                            <th>Lab Results:</th>
                            <td>{{ $testType->labResults->count() }}</td>
                        </tr>
                        <tr>
                            <th>Created:</th>
                            <td>{{ $testType->created_at->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td>{{ $testType->updated_at->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <th>Created By:</th>
                            <td>{{ $testType->createdBy->name ?? 'Unknown' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-dark"><i class="bi bi-boxes"></i> Consumables per Test</h5>
                    @can('manage_lab_test_consumables')
                    <a href="{{ route('lab.test-types.edit', $testType) }}#consumableLines" class="btn btn-sm btn-outline-primary">Edit Mapping</a>
                    @endcan
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="table-light"><tr><th>Type</th><th>Item</th><th>Qty / Test</th><th>Optional</th><th>Notes</th></tr></thead>
                        <tbody>
                            @forelse($testType->consumableItems as $item)
                            <tr>
                                <td><span class="badge bg-info text-dark">{{ ucfirst($item->item_type) }}</span></td>
                                <td>{{ $item->getItemName() }}</td>
                                <td>{{ $item->quantity_per_test }}</td>
                                <td>{{ $item->is_optional ? 'Yes' : 'No' }}</td>
                                <td>{{ $item->notes ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No consumables linked — stock will not auto-deduct for this test.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($testType->tests->count() > 0)
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Related Tests</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @foreach($testType->tests->take(5) as $test)
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">{{ $test->test_name }}</h6>
                                <small>{{ $test->created_at->format('M d') }}</small>
                            </div>
                            <p class="mb-1 small">{{ $test->description ?? 'No description' }}</p>
                            <small>
                                <span class="badge bg-{{ $test->is_active ? 'success' : 'secondary' }}">
                                    {{ $test->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </small>
                        </div>
                        @endforeach
                    </div>
                    @if($testType->tests->count() > 5)
                    <div class="text-center mt-3">
                        <small class="text-muted">And {{ $testType->tests->count() - 5 }} more...</small>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
