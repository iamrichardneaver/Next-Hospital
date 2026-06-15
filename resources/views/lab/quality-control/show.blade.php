@extends('layouts.app')

@section('title', 'QC Record Details')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1" style="color: #1e3a5f;">
                    <i class="bi bi-clipboard-check"></i> QC Record Details
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('lab.quality-control.index') }}">Quality Control</a></li>
                        <li class="breadcrumb-item active">Record Details</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('lab.quality-control.edit', $qualityControl) }}" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="{{ route('lab.quality-control.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">QC Test Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Test Parameter:</th>
                            <td><strong>{{ $qualityControl->parameter ? $qualityControl->parameter->parameter_name : 'N/A' }}</strong></td>
                        </tr>
                        <tr>
                            <th>Template:</th>
                            <td>{{ $qualityControl->parameter && $qualityControl->parameter->template ? $qualityControl->parameter->template->template_name : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>QC Type:</th>
                            <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $qualityControl->qc_type)) }}</span></td>
                        </tr>
                        <tr>
                            <th>QC Level:</th>
                            <td>{{ ucfirst(str_replace('_', ' ', $qualityControl->qc_level)) }}</td>
                        </tr>
                        <tr>
                            <th>QC Material:</th>
                            <td>{{ $qualityControl->qc_material }}</td>
                        </tr>
                        <tr>
                            <th>Lot Number:</th>
                            <td>{{ $qualityControl->lot_number }}</td>
                        </tr>
                        <tr>
                            <th>Expiry Date:</th>
                            <td>{{ $qualityControl->expiry_date ? $qualityControl->expiry_date->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Test Results</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Target Value:</th>
                            <td><strong>{{ number_format($qualityControl->target_value, 4) }}</strong></td>
                        </tr>
                        <tr>
                            <th>Measured Value:</th>
                            <td><strong class="{{ $qualityControl->is_acceptable ? 'text-success' : 'text-danger' }}">
                                {{ number_format($qualityControl->measured_value, 4) }}
                            </strong></td>
                        </tr>
                        <tr>
                            <th>Acceptable Range:</th>
                            <td>{{ number_format($qualityControl->acceptable_range_low, 4) }} - {{ number_format($qualityControl->acceptable_range_high, 4) }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                @if($qualityControl->is_acceptable)
                                    <span class="badge bg-success" style="font-size: 1rem;">
                                        <i class="bi bi-check-circle"></i> PASSED
                                    </span>
                                @else
                                    <span class="badge bg-danger" style="font-size: 1rem;">
                                        <i class="bi bi-x-circle"></i> FAILED
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @if($qualityControl->notes)
                        <tr>
                            <th>Notes:</th>
                            <td>{{ $qualityControl->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Audit Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Performed By:</strong><br>
                        {{ $qualityControl->performedBy ? $qualityControl->performedBy->firstname . ' ' . $qualityControl->performedBy->lastname : 'N/A' }}
                    </p>
                    <p class="mb-2">
                        <strong>Performed At:</strong><br>
                        {{ $qualityControl->performed_at ? $qualityControl->performed_at->format('M d, Y H:i') : 'N/A' }}
                    </p>
                    <p class="mb-0">
                        <strong>Last Updated:</strong><br>
                        {{ $qualityControl->updated_at->format('M d, Y H:i') }}
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('lab.quality-control.edit', $qualityControl) }}" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Record
                        </a>
                        <form action="{{ route('lab.quality-control.destroy', $qualityControl) }}" method="POST" 
                              onsubmit="return confirm('Are you sure you want to delete this QC record?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-trash"></i> Delete Record
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
