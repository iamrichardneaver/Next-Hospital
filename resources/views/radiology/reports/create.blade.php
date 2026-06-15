@extends('layouts.app')

@section('title', 'Create Radiology Report')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Create Radiology Report</h1>
                <p class="page-subtitle">Study: {{ $study->modality->name }}</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('radiology.studies.show', $study) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Study
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('radiology.reports.store', $study) }}" method="POST" id="reportForm">
        @csrf
        
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
                                    @if($study->request && $study->request->patient)
                                        <p class="form-control-plaintext">{{ $study->request->patient->first_name }} {{ $study->request->patient->last_name }} ({{ $study->request->patient->patient_number }})</p>
                                    @elseif($study->patient)
                                        <p class="form-control-plaintext">{{ $study->patient->first_name }} {{ $study->patient->last_name }} ({{ $study->patient->patient_number }})</p>
                                    @else
                                        <p class="form-control-plaintext text-muted">No patient data</p>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Modality</label>
                                    <p class="form-control-plaintext">{{ $study->modality ? $study->modality->name : 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Study Date</label>
                                    <p class="form-control-plaintext">{{ $study->study_date->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Study Description</label>
                                    <p class="form-control-plaintext">{{ $study->study_description }}</p>
                                </div>
                            </div>
                        </div>
                        @if($study->study_notes)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Study Notes</label>
                            <div class="border rounded p-3 bg-light">
                                {!! $study->study_notes !!}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Clinical Information -->
                @if($study->request)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Clinical Information</h5>
                    </div>
                    <div class="card-body">
                        @if($study->request->clinical_history)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Clinical History</label>
                            <div class="border rounded p-3 bg-light">
                                {!! $study->request->clinical_history !!}
                            </div>
                        </div>
                        @endif
                        @if($study->request->clinical_question)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Clinical Question</label>
                            <div class="border rounded p-3 bg-light">
                                {!! $study->request->clinical_question !!}
                            </div>
                        </div>
                        @endif
                        @if(!$study->request->clinical_history && !$study->request->clinical_question)
                            <p class="text-muted">No clinical information available</p>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Study Images Selection -->
                @php
                    $allImages = collect();
                    if ($study->series) {
                        foreach ($study->series as $series) {
                            if ($series->images) {
                                $allImages = $allImages->merge($series->images);
                            }
                        }
                    }
                @endphp
                
                @if($allImages->count() > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Select Key Images for Report</h5>
                            <span class="badge bg-primary">{{ $allImages->count() }} Images Available</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Select the images you want to include in this report (optional)</p>
                        @foreach($study->series as $series)
                            @if($series->images && $series->images->count() > 0)
                                <div class="mb-4">
                                    <h6 class="text-primary mb-3">
                                        <i class="bi bi-collection"></i> Series: {{ $series->series_description }}
                                    </h6>
                                    <div class="row">
                                        @foreach($series->images as $image)
                                        <div class="col-md-3 col-sm-6 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body text-center">
                                                    @php
                                                        $imagePath = $image->file_path;
                                                        $fullPath = storage_path('app/' . $imagePath);
                                                        $publicPath = str_replace('app/', 'app/public/', $fullPath);
                                                    @endphp
                                                    @if($imagePath && (file_exists($fullPath) || file_exists($publicPath)))
                                                        @if(str_contains($image->mime_type ?? '', 'image'))
                                                            @php
                                                                // Use dynamic route to serve images (handles 403 errors)
                                                                $imgUrl = route('radiology.images.serve', $image->id);
                                                            @endphp
                                                            <img src="{{ $imgUrl }}" 
                                                                 alt="Image {{ $image->instance_number }}" 
                                                                 class="img-fluid mb-2" 
                                                                 style="max-height: 150px; object-fit: contain;">
                                                        @else
                                                            <div class="bg-light p-4 mb-2" style="height: 150px; display: flex; align-items: center; justify-content: center;">
                                                                <i class="bi bi-file-earmark-medical text-muted" style="font-size: 3rem;"></i>
                                                            </div>
                                                        @endif
                                                    @else
                                                        <div class="bg-light p-4 mb-2" style="height: 150px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                                        </div>
                                                    @endif
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="selected_images[]" 
                                                               value="{{ $image->id }}" id="image{{ $image->id }}">
                                                        <label class="form-check-label" for="image{{ $image->id }}">
                                                            Image #{{ $image->instance_number }}
                                                        </label>
                                                    </div>
                                                    @if($image->image_description)
                                                    <small class="text-muted d-block mt-1">{{ $image->image_description }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i> Selected images will be attached to the report and included in the PDF.
                        </div>
                    </div>
                </div>
                @endif

                <!-- Report Content -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Report Content</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="findings" class="form-label">Findings <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('findings') is-invalid @enderror" 
                                      id="findings" name="findings" rows="8" required>{{ old('findings') }}</textarea>
                            @error('findings')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Describe the radiological findings in detail. Use proper medical terminology and be specific about anatomical locations.</div>
                        </div>
                        <div class="mb-3">
                            <label for="impression" class="form-label">Impression <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('impression') is-invalid @enderror" 
                                      id="impression" name="impression" rows="6" required>{{ old('impression') }}</textarea>
                            @error('impression')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Provide your diagnostic impression based on the findings. Include differential diagnoses if applicable.</div>
                        </div>
                        <div class="mb-3">
                            <label for="recommendations" class="form-label">Recommendations</label>
                            <textarea class="form-control @error('recommendations') is-invalid @enderror" 
                                      id="recommendations" name="recommendations" rows="4">{{ old('recommendations') }}</textarea>
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
                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="preliminary" {{ old('status') == 'preliminary' ? 'selected' : '' }}>Preliminary</option>
                                <option value="final" {{ old('status') == 'final' ? 'selected' : '' }}>Final</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <strong>Draft:</strong> Work in progress, not yet ready for review<br>
                                <strong>Preliminary:</strong> Ready for review but may be modified<br>
                                <strong>Final:</strong> Complete and ready for clinical use
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('radiology.studies.show', $study) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Report
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
@endpush
