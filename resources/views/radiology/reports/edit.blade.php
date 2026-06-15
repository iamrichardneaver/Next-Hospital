@extends('layouts.app')

@section('title', 'Edit Radiology Report')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Edit Radiology Report</h1>
                <p class="page-subtitle">Report #{{ $report->id }}</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('radiology.reports.show', $report) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Report
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('radiology.reports.update', $report) }}" method="POST" id="reportForm">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Study Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Study Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Patient</label>
                                    <p class="form-control-plaintext">{{ $report->study->request->patient->full_name }} ({{ $report->study->request->patient->patient_number }})</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Modality</label>
                                    <p class="form-control-plaintext">{{ $report->study->modality->name }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Study Date</label>
                                    <p class="form-control-plaintext">{{ $report->study->study_date->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Study Description</label>
                                    <p class="form-control-plaintext">{{ $report->study->study_description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clinical Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Clinical Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Clinical History</label>
                            <div class="border rounded p-3 bg-light">
                                {!! $report->study->request->clinical_history !!}
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Clinical Question</label>
                            <div class="border rounded p-3 bg-light">
                                {!! $report->study->request->clinical_question !!}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Content -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Report Content</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="findings" class="form-label">Findings <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('findings') is-invalid @enderror" 
                                      id="findings" name="findings" rows="8" required>{{ old('findings', $report->findings) }}</textarea>
                            @error('findings')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Describe the radiological findings in detail. Use proper medical terminology and be specific about anatomical locations.</div>
                        </div>
                        <div class="mb-3">
                            <label for="impression" class="form-label">Impression <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('impression') is-invalid @enderror" 
                                      id="impression" name="impression" rows="6" required>{{ old('impression', $report->impression) }}</textarea>
                            @error('impression')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Provide your diagnostic impression based on the findings. Include differential diagnoses if applicable.</div>
                        </div>
                        <div class="mb-3">
                            <label for="recommendations" class="form-label">Recommendations</label>
                            <textarea class="form-control @error('recommendations') is-invalid @enderror" 
                                      id="recommendations" name="recommendations" rows="4">{{ old('recommendations', $report->recommendations) }}</textarea>
                            @error('recommendations')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Include any follow-up recommendations, additional imaging studies, or clinical correlations needed.</div>
                        </div>
                    </div>
                </div>

                <!-- Report Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Report Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                        <option value="draft" {{ old('status', $report->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="preliminary" {{ old('status', $report->status) == 'preliminary' ? 'selected' : '' }}>Preliminary</option>
                                        <option value="final" {{ old('status', $report->status) == 'final' ? 'selected' : '' }}>Final</option>
                                        <option value="amended" {{ old('status', $report->status) == 'amended' ? 'selected' : '' }}>Amended</option>
                                        <option value="cancelled" {{ old('status', $report->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-text">
                            <strong>Draft:</strong> Work in progress, not yet ready for review<br>
                            <strong>Preliminary:</strong> Ready for review but may be modified<br>
                            <strong>Final:</strong> Complete and ready for clinical use<br>
                            <strong>Amended:</strong> Previously final report that has been modified<br>
                            <strong>Cancelled:</strong> Report has been cancelled
                        </div>
                    </div>
                </div>

                <!-- Amendment Information -->
                <div class="card mb-4" id="amendmentSection" style="display: none;">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Amendment Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="amendment_reason" class="form-label">Amendment Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('amendment_reason') is-invalid @enderror" 
                                      id="amendment_reason" name="amendment_reason" rows="3">{{ old('amendment_reason', $report->amendment_reason) }}</textarea>
                            @error('amendment_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Explain why this report is being amended. This information will be included in the report.</div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('radiology.reports.show', $report) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/ckeditor-config.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const amendmentSection = document.getElementById('amendmentSection');
    const amendmentReasonField = document.getElementById('amendment_reason');
    
    function toggleAmendmentSection() {
        if (statusSelect.value === 'amended') {
            amendmentSection.style.display = 'block';
            amendmentReasonField.required = true;
        } else {
            amendmentSection.style.display = 'none';
            amendmentReasonField.required = false;
        }
    }
    
    // Initial check
    toggleAmendmentSection();
    
    // Listen for changes
    statusSelect.addEventListener('change', toggleAmendmentSection);
});
</script>
@endpush
