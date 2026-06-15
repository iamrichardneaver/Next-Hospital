@extends('layouts.app')

@section('title', 'Enter Test Results')

@section('content')
@php $templatesToShow = $templatesToShow ?? collect([$labRequest->template])->filter(); @endphp
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-1" style="color: #1e3a5f;">Enter Test Results</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('lab.index') }}">Lab Requests</a></li>
                <li class="breadcrumb-item"><a href="{{ route('lab.show', $labRequest) }}">{{ $labRequest->request_number }}</a></li>
                <li class="breadcrumb-item active">Enter Results</li>
            </ol>
        </nav>
    </div>
    
    <!-- Customer Info Card -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Customer:</strong> {{ $labRequest->patient->first_name }} {{ $labRequest->patient->last_name }}<br>
                    <strong>Age:</strong> {{ $labRequest->patient->date_of_birth ? \Carbon\Carbon::parse($labRequest->patient->date_of_birth)->age : 'N/A' }} years<br>
                    <strong>Gender:</strong> {{ $labRequest->patient->gender ?? 'N/A' }}
                </div>
                <div class="col-md-4">
                    <strong>Request #:</strong> {{ $labRequest->request_number }}<br>
                    <strong>Test(s):</strong> @foreach($templatesToShow as $t){{ $t->template_name }}@if(!$loop->last), @endif @endforeach<br>
                    <strong>Count:</strong> <span class="badge bg-primary">{{ $templatesToShow->count() }} test(s)</span>
                </div>
                <div class="col-md-4">
                    <strong>Doctor:</strong> {{ $labRequest->doctor->firstname ?? 'N/A' }} {{ $labRequest->doctor->lastname ?? '' }}<br>
                    <strong>Date:</strong> {{ $labRequest->created_at->format('M d, Y') }}<br>
                    <strong>Specimen:</strong> {{ $templatesToShow->first()->specimen_type ?? 'N/A' }}
                </div>
            </div>
        </div>
    </div>
    
    <!-- Results Entry Form (all tests in this request) -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 text-dark">Enter results for all tests in this request</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('lab.store-results', $labRequest) }}" method="POST" id="resultsForm">
                @csrf
                @foreach($templatesToShow as $template)
                <div class="border rounded p-3 mb-4" style="background: #f8f9fa;">
                    <h5 class="text-primary mb-3"><i class="bi bi-droplet-half"></i> {{ $template->template_name }}</h5>
                @if($template->template_type === 'quantitative' || $template->template_type === 'combined')
                    <!-- Quantitative Parameters -->
                    <h6 class="mb-3 text-primary"><i class="bi bi-123"></i> Quantitative Parameters</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 25%;">Parameter</th>
                                    <th style="width: 20%;">Result Value</th>
                                    <th style="width: 10%;">Unit</th>
                                    <th style="width: 25%;">Reference Range</th>
                                    <th style="width: 20%;">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($template->parameters->where('data_type', 'numeric') as $paramIndex => $parameter)
                                <tr>
                                    <td>
                                        <strong>{{ $parameter->parameter_name }}</strong>
                                        @if($parameter->is_required)
                                            <span class="text-danger">*</span>
                                        @endif
                                        @if($parameter->is_critical)
                                            <span class="badge bg-danger badge-sm">Critical</span>
                                        @endif
                                        <input type="hidden" name="results[{{ $template->id }}][{{ $paramIndex }}][parameter_id]" value="{{ $parameter->id }}">
                                    </td>
                                    <td>
                                        <input type="number" 
                                               class="form-control" 
                                               name="results[{{ $template->id }}][{{ $paramIndex }}][result_value]" 
                                               step="{{ $parameter->decimal_places > 0 ? '0.' . str_repeat('0', $parameter->decimal_places - 1) . '1' : '1' }}"
                                               {{ $parameter->is_required ? 'required' : '' }}
                                               placeholder="Enter value">
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $parameter->unit ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $patient = $labRequest->patient;
                                            $gender = $patient->gender ?? 'Both';
                                            $age = $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : null;
                                            $ageGroup = 'Adult';
                                            if ($age < 1) $ageGroup = 'Newborn';
                                            elseif ($age < 2) $ageGroup = 'Infant';
                                            elseif ($age < 12) $ageGroup = 'Child';
                                            elseif ($age < 18) $ageGroup = 'Adolescent';
                                            elseif ($age < 65) $ageGroup = 'Adult';
                                            else $ageGroup = 'Elderly';
                                            
                                            $refRange = $parameter->referenceRanges
                                                ->where(function($range) use ($gender) {
                                                    return $range->gender == $gender || $range->gender == 'Both';
                                                })
                                                ->where('age_group', $ageGroup)
                                                ->where('is_active', true)
                                                ->first();
                                        @endphp
                                        <small class="text-info">
                                            @if($refRange)
                                                {{ $refRange->getFormattedRange() }}
                                            @else
                                                <em>No ref range</em>
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        <input type="text" 
                                               class="form-control form-control-sm" 
                                               name="results[{{ $template->id }}][{{ $paramIndex }}][technical_notes]" 
                                               placeholder="Notes">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                
                @if($template->template_type === 'qualitative' || $template->template_type === 'combined')
                    @php $numNumeric = $template->parameters->where('data_type', 'numeric')->count(); $qualParams = $template->parameters->whereIn('input_type', ['select', 'radio']); @endphp
                    <!-- Qualitative Parameters -->
                    <h6 class="mb-3 text-success"><i class="bi bi-list-check"></i> Qualitative Parameters</h6>
                    <div class="row mb-4">
                        @foreach($qualParams as $qIndex => $parameter)
                        @php $qualIndex = $numNumeric + $qIndex; @endphp
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <strong>{{ $parameter->parameter_name }}</strong>
                                @if($parameter->is_required) <span class="text-danger">*</span> @endif
                            </label>
                            <input type="hidden" name="results[{{ $template->id }}][{{ $qualIndex }}][parameter_id]" value="{{ $parameter->id }}">
                            
                            @if($parameter->input_type === 'select')
                                <select class="form-select" name="results[{{ $template->id }}][{{ $qualIndex }}][result_value]" {{ $parameter->is_required ? 'required' : '' }}>
                                    <option value="">-- Select --</option>
                                    @if($parameter->input_options)
                                        @foreach($parameter->input_options as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    @else
                                        <option value="Positive">Positive</option>
                                        <option value="Negative">Negative</option>
                                        <option value="Indeterminate">Indeterminate</option>
                                    @endif
                                </select>
                            @else
                                <div>
                                    @php $options = $parameter->input_options ?? ['Positive', 'Negative', 'Indeterminate']; @endphp
                                    @foreach($options as $option)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" 
                                                   name="results[{{ $template->id }}][{{ $qualIndex }}][result_value]" 
                                                   id="param_{{ $template->id }}_{{ $parameter->id }}_{{ $loop->index }}"
                                                   value="{{ $option }}" {{ $parameter->is_required ? 'required' : '' }}>
                                            <label class="form-check-label" for="param_{{ $template->id }}_{{ $parameter->id }}_{{ $loop->index }}">
                                                {{ $option }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @endif
                
                @if($template->template_type === 'narrative' || $template->template_type === 'combined')
                    @php $narrativeParam = $template->parameters->where('input_type', 'rich_text')->first(); $narrativeIndex = $template->parameters->where('data_type', 'numeric')->count() + $template->parameters->whereIn('input_type', ['select', 'radio'])->count(); @endphp
                    <!-- Narrative Content -->
                    <h6 class="mb-3 text-warning"><i class="bi bi-file-text"></i> Narrative Results</h6>
                    <div class="mb-4">
                        @if($template->template_content)
                            <div class="alert alert-info">
                                <strong>Template Guide:</strong>
                                <div>{!! $template->template_content !!}</div>
                            </div>
                        @endif
                        
                        @if($narrativeParam)
                            <input type="hidden" name="results[{{ $template->id }}][{{ $narrativeIndex }}][parameter_id]" value="{{ $narrativeParam->id }}">
                            <label class="form-label"><strong>Detailed Findings ({{ $template->template_name }})</strong></label>
                            <textarea class="form-control narrative-editor" id="narrative_result_{{ $template->id }}" name="results[{{ $template->id }}][{{ $narrativeIndex }}][result_value]" rows="8" 
                                      placeholder="Enter detailed narrative results..."></textarea>
                        @endif
                    </div>
                @endif
                
                @endforeach
                
                <!-- Test Metadata (shared for entire request) -->
                <hr>
                <h6 class="mb-3">Test Metadata</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Methodology Used</label>
                        <input type="text" class="form-control" name="methodology_used" 
                               value="{{ $templatesToShow->first()->methodology ?? '' }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Equipment Used</label>
                        <input type="text" class="form-control" name="equipment_used" 
                               value="{{ $templatesToShow->first()->equipment_required ?? '' }}">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Reagent Lot Number</label>
                        <input type="text" class="form-control" name="reagent_lot_number">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Reagent Expiry Date</label>
                        <input type="date" class="form-control" name="reagent_expiry_date">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Technician Notes</label>
                    <textarea class="form-control" name="technician_notes" rows="2"></textarea>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle"></i> Save Results
                    </button>
                    <a href="{{ route('lab.show', $labRequest) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@php $hasNarrative = $templatesToShow->contains(fn($t) => in_array($t->template_type, ['narrative', 'combined'])); @endphp
@if($hasNarrative)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editorConfig = {
        toolbar: {
            items: [
                'heading', '|',
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'bulletedList', 'numberedList', '|',
                'outdent', 'indent', '|',
                'blockQuote', 'insertTable', '|',
                'undo', 'redo'
            ]
        },
        language: 'en',
        table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'] },
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
            ]
        }
    };
    document.querySelectorAll('.narrative-editor').forEach(function(el) {
        ClassicEditor.create(el, editorConfig)
            .then(editor => { editor.ui.view.editable.element.classList.add('ck-editor__editable--medical'); })
            .catch(err => console.error('CKEditor init error:', err));
    });
});
</script>
@endif
@endsection

