@extends('layouts.app')

@section('title', 'Edit Test Type')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Edit Test Type</h1>
            <p class="text-secondary mb-0">Update laboratory test type information</p>
        </div>
        <div>
            <a href="{{ route('lab.test-types') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Test Types
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Test Type Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('lab.test-types.update', $testType) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="test_code" class="form-label">Test Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('test_code') is-invalid @enderror" 
                                       id="test_code" name="test_code" value="{{ old('test_code', $testType->test_code) }}" required>
                                @error('test_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="test_name" class="form-label">Test Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('test_name') is-invalid @enderror" 
                                       id="test_name" name="test_name" value="{{ old('test_name', $testType->test_name) }}" required>
                                @error('test_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select @error('category') is-invalid @enderror" 
                                        id="category" name="category" required>
                                    <option value="">-- Select Category --</option>
                                    <option value="Hematology" {{ old('category', $testType->category) == 'Hematology' ? 'selected' : '' }}>Hematology</option>
                                    <option value="Biochemistry" {{ old('category', $testType->category) == 'Biochemistry' ? 'selected' : '' }}>Biochemistry</option>
                                    <option value="Microbiology" {{ old('category', $testType->category) == 'Microbiology' ? 'selected' : '' }}>Microbiology</option>
                                    <option value="Serology" {{ old('category', $testType->category) == 'Serology' ? 'selected' : '' }}>Serology</option>
                                    <option value="Urine Analysis" {{ old('category', $testType->category) == 'Urine Analysis' ? 'selected' : '' }}>Urine Analysis</option>
                                    <option value="Other" {{ old('category', $testType->category) == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="subcategory" class="form-label">Subcategory</label>
                                <input type="text" class="form-control @error('subcategory') is-invalid @enderror" 
                                       id="subcategory" name="subcategory" value="{{ old('subcategory', $testType->subcategory) }}">
                                @error('subcategory')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="template_id" class="form-label">Result template</label>
                            <select class="form-select @error('template_id') is-invalid @enderror" id="template_id" name="template_id">
                                <option value="">— No template (results cannot be entered until assigned) —</option>
                                @foreach($templates as $t)
                                    <option value="{{ $t->id }}" {{ old('template_id', $testType->template_id) == $t->id ? 'selected' : '' }}>
                                        {{ $t->template_name }} ({{ $t->template_code }}) — {{ $t->parameters->count() }} parameters
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Required for entering lab results. Create templates under <a href="{{ route('lab.templates') }}">Templates</a>, add parameters, then assign here.</small>
                            @error('template_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $testType->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="specimen_type" class="form-label">Specimen Type <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('specimen_type') is-invalid @enderror" 
                                       id="specimen_type" name="specimen_type" value="{{ old('specimen_type', $testType->specimen_type) }}" required>
                                @error('specimen_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="collection_method" class="form-label">Collection Method</label>
                                <input type="text" class="form-control @error('collection_method') is-invalid @enderror" 
                                       id="collection_method" name="collection_method" value="{{ old('collection_method', $testType->collection_method) }}">
                                @error('collection_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="routine_tat_hours" class="form-label">Routine TAT (Hours)</label>
                                <input type="number" class="form-control @error('routine_tat_hours') is-invalid @enderror" 
                                       id="routine_tat_hours" name="routine_tat_hours" value="{{ old('routine_tat_hours', $testType->routine_tat_hours) }}" min="1">
                                @error('routine_tat_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="urgent_tat_hours" class="form-label">Urgent TAT (Hours)</label>
                                <input type="number" class="form-control @error('urgent_tat_hours') is-invalid @enderror" 
                                       id="urgent_tat_hours" name="urgent_tat_hours" value="{{ old('urgent_tat_hours', $testType->urgent_tat_hours) }}" min="1">
                                @error('urgent_tat_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="stat_tat_hours" class="form-label">STAT TAT (Hours)</label>
                                <input type="number" class="form-control @error('stat_tat_hours') is-invalid @enderror" 
                                       id="stat_tat_hours" name="stat_tat_hours" value="{{ old('stat_tat_hours', $testType->stat_tat_hours) }}" min="1">
                                @error('stat_tat_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cost" class="form-label">Cost (GHS)</label>
                                <input type="number" class="form-control @error('cost') is-invalid @enderror" 
                                       id="cost" name="cost" value="{{ old('cost', $testType->cost) }}" step="0.01" min="0">
                                @error('cost')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="nhis_cost" class="form-label">NHIS Cost (GHS)</label>
                                <input type="number" class="form-control @error('nhis_cost') is-invalid @enderror" 
                                       id="nhis_cost" name="nhis_cost" value="{{ old('nhis_cost', $testType->nhis_cost) }}" step="0.01" min="0">
                                @error('nhis_cost')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="methodology" class="form-label">Methodology</label>
                                <input type="text" class="form-control @error('methodology') is-invalid @enderror" 
                                       id="methodology" name="methodology" value="{{ old('methodology', $testType->methodology) }}">
                                @error('methodology')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="equipment_required" class="form-label">Equipment Required</label>
                                <input type="text" class="form-control @error('equipment_required') is-invalid @enderror" 
                                       id="equipment_required" name="equipment_required" value="{{ old('equipment_required', $testType->equipment_required) }}">
                                @error('equipment_required')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ghs_code" class="form-label">GHS Code</label>
                            <input type="text" class="form-control @error('ghs_code') is-invalid @enderror" 
                                   id="ghs_code" name="ghs_code" value="{{ old('ghs_code', $testType->ghs_code) }}">
                            @error('ghs_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="nhis_covered" name="nhis_covered" 
                                           {{ old('nhis_covered', $testType->nhis_covered) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="nhis_covered">
                                        NHIS Covered
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="requires_doctor_approval" name="requires_doctor_approval" 
                                           {{ old('requires_doctor_approval', $testType->requires_doctor_approval) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_doctor_approval">
                                        Requires Doctor Approval
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="requires_consultant_review" name="requires_consultant_review" 
                                           {{ old('requires_consultant_review', $testType->requires_consultant_review) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_consultant_review">
                                        Requires Consultant Review
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="requires_qc" name="requires_qc" 
                                           {{ old('requires_qc', $testType->requires_qc) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_qc">
                                        Requires Quality Control
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="requires_verification" name="requires_verification" 
                                           {{ old('requires_verification', $testType->requires_verification) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_verification">
                                        Requires Verification
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="ghs_mandatory" name="ghs_mandatory" 
                                           {{ old('ghs_mandatory', $testType->ghs_mandatory) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ghs_mandatory">
                                        GHS Mandatory Reporting
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   {{ old('is_active', $testType->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('lab.test-types') }}" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Test Type
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Test Type Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
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
                        <tr>
                            <th>Tests Using This Type:</th>
                            <td>{{ $testType->tests->count() }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @can('manage_lab_test_consumables')
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-dark"><i class="bi bi-boxes"></i> Consumables per Test</h5>
                    <span class="text-muted small">Auto-deducted when this test is completed</span>
                </div>
                <div class="card-body">
                    @if(session('success') && str_contains(session('success'), 'consumables'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                    <form method="POST" action="{{ route('lab.test-types.consumables.sync', $testType) }}">
                        @csrf
                        <div id="consumableLines">
                            @forelse($testType->consumableItems as $idx => $line)
                            <div class="row g-2 mb-2 consumable-line">
                                <div class="col-md-2">
                                    <select name="items[{{ $idx }}][item_type]" class="form-select" required>
                                        <option value="reagent" @selected($line->item_type === 'reagent')>Reagent</option>
                                        <option value="consumable" @selected($line->item_type === 'consumable')>Consumable</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select name="items[{{ $idx }}][item_id]" class="form-select" required>
                                        <optgroup label="Reagents">
                                            @foreach($reagents as $r)
                                            <option value="{{ $r->id }}" @selected($line->item_type === 'reagent' && $line->item_id == $r->id)>{{ $r->name }}</option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="Consumables">
                                            @foreach($consumables as $c)
                                            <option value="{{ $c->id }}" @selected($line->item_type === 'consumable' && $line->item_id == $c->id)>{{ $c->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" step="0.01" min="0.01" name="items[{{ $idx }}][quantity_per_test]" class="form-control" value="{{ $line->quantity_per_test }}" required placeholder="Qty/test">
                                </div>
                                <div class="col-md-2">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="items[{{ $idx }}][is_optional]" value="1" @checked($line->is_optional)>
                                        <label class="form-check-label">Optional</label>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="items[{{ $idx }}][notes]" class="form-control" value="{{ $line->notes }}" placeholder="Notes">
                                </div>
                            </div>
                            @empty
                            <div class="row g-2 mb-2 consumable-line">
                                <div class="col-md-2">
                                    <select name="items[0][item_type]" class="form-select"><option value="reagent">Reagent</option><option value="consumable">Consumable</option></select>
                                </div>
                                <div class="col-md-4">
                                    <select name="items[0][item_id]" class="form-select">
                                        <option value="">Select item</option>
                                        <optgroup label="Reagents">@foreach($reagents as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</optgroup>
                                        <optgroup label="Consumables">@foreach($consumables as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</optgroup>
                                    </select>
                                </div>
                                <div class="col-md-2"><input type="number" step="0.01" min="0.01" name="items[0][quantity_per_test]" class="form-control" value="1"></div>
                                <div class="col-md-2"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="items[0][is_optional]" value="1"><label class="form-check-label">Optional</label></div></div>
                                <div class="col-md-2"><input type="text" name="items[0][notes]" class="form-control" placeholder="Notes"></div>
                            </div>
                            @endforelse
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addConsumableLine()"><i class="bi bi-plus"></i> Add Item</button>
                            <button type="submit" class="btn btn-sm btn-success">Save Consumables Mapping</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endcan
</div>

@can('manage_lab_test_consumables')
@push('scripts')
<script>
let consumableIdx = {{ max($testType->consumableItems->count(), 1) }};
const reagentOpts = `@foreach($reagents as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach`;
const consumableOpts = `@foreach($consumables as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach`;
function addConsumableLine() {
    document.getElementById('consumableLines').insertAdjacentHTML('beforeend',
        `<div class="row g-2 mb-2 consumable-line">
            <div class="col-md-2"><select name="items[${consumableIdx}][item_type]" class="form-select"><option value="reagent">Reagent</option><option value="consumable">Consumable</option></select></div>
            <div class="col-md-4"><select name="items[${consumableIdx}][item_id]" class="form-select"><optgroup label="Reagents">${reagentOpts}</optgroup><optgroup label="Consumables">${consumableOpts}</optgroup></select></div>
            <div class="col-md-2"><input type="number" step="0.01" min="0.01" name="items[${consumableIdx}][quantity_per_test]" class="form-control" value="1"></div>
            <div class="col-md-2"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="items[${consumableIdx}][is_optional]" value="1"><label class="form-check-label">Optional</label></div></div>
            <div class="col-md-2"><input type="text" name="items[${consumableIdx}][notes]" class="form-control" placeholder="Notes"></div>
        </div>`);
    consumableIdx++;
}
</script>
@endpush
@endcan
@endsection
