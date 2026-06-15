@extends('layouts.app')

@section('title', 'Create Lab Request from Walk-in Visit')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Create Lab Request</h1>
            <p class="text-secondary mb-0">Create lab request for walk-in patient</p>
        </div>
        <a href="{{ route('walk-ins.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Walk-ins
        </a>
    </div>

    <!-- Patient Information Card -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-person-check"></i> Patient Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Patient Name:</strong><br>
                    {{ $visit->patient->first_name }} {{ $visit->patient->last_name }}
                </div>
                <div class="col-md-3">
                    <strong>Patient Number:</strong><br>
                    {{ $visit->patient->patient_number }}
                </div>
                <div class="col-md-3">
                    <strong>Visit Token:</strong><br>
                    <span class="badge badge-light-primary">{{ $visit->visit_token }}</span>
                </div>
                <div class="col-md-3">
                    <strong>Visit Type:</strong><br>
                    <span class="badge badge-light-info">{{ $visit->visit_type }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clipboard-plus"></i> Lab Test Selection</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('lab.store-from-walk-in', $visit->id) }}" method="POST" id="labRequestForm">
                        @csrf
                        
                        <!-- Test Templates Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Lab Tests <span class="text-danger">*</span></label>
                            <div class="row" id="testTemplatesContainer">
                                @foreach($testTemplates as $template)
                                <div class="col-md-6 mb-3">
                                    <div class="card test-template-card" data-template-id="{{ $template->id }}" data-cost="{{ $template->cost ?? 0 }}">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input test-template-checkbox" 
                                                       type="checkbox" 
                                                       name="template_ids[]" 
                                                       value="{{ $template->id }}" 
                                                       id="template_{{ $template->id }}"
                                                       data-cost="{{ $template->cost ?? 0 }}">
                                                <label class="form-check-label w-100" for="template_{{ $template->id }}">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong>{{ $template->template_name }}</strong>
                                                            <br>
                                                            <small class="text-muted">{{ $template->description }}</small>
                                                            <br>
                                                            <small class="text-info">
                                                                <i class="bi bi-droplet"></i> {{ $template->specimen_type }}
                                                            </small>
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="badge badge-light-success cost-badge">
                                                                GHS {{ number_format($template->cost ?? 0, 2) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Clinical Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                    <option value="routine" {{ old('priority') == 'routine' ? 'selected' : '' }}>Routine</option>
                                    <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                    <option value="stat" {{ old('priority') == 'stat' ? 'selected' : '' }}>STAT</option>
                                </select>
                                @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="specimen_type" class="form-label">Specimen Type</label>
                                <input type="text" 
                                       class="form-control @error('specimen_type') is-invalid @enderror" 
                                       id="specimen_type" 
                                       name="specimen_type" 
                                       value="{{ old('specimen_type') }}"
                                       placeholder="e.g., Blood, Urine, Stool">
                                @error('specimen_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="clinical_notes" class="form-label">Clinical Notes</label>
                            <textarea class="form-control @error('clinical_notes') is-invalid @enderror" 
                                      id="clinical_notes" 
                                      name="clinical_notes" 
                                      rows="3" 
                                      placeholder="Enter clinical notes or reason for testing">{{ old('clinical_notes') }}</textarea>
                            @error('clinical_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label for="collection_instructions" class="form-label">Collection Instructions</label>
                            <textarea class="form-control @error('collection_instructions') is-invalid @enderror" 
                                      id="collection_instructions" 
                                      name="collection_instructions" 
                                      rows="2" 
                                      placeholder="Special collection instructions">{{ old('collection_instructions') }}</textarea>
                            @error('collection_instructions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label for="special_instructions" class="form-label">Special Instructions</label>
                            <textarea class="form-control @error('special_instructions') is-invalid @enderror" 
                                      id="special_instructions" 
                                      name="special_instructions" 
                                      rows="2" 
                                      placeholder="Any special instructions for the lab">{{ old('special_instructions') }}</textarea>
                            @error('special_instructions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <!-- Billing Options -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-credit-card"></i> Billing Options</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="create_invoice" 
                                           value="1" 
                                           id="create_invoice"
                                           {{ old('create_invoice') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="create_invoice">
                                        <strong>Generate Invoice Automatically</strong>
                                        <br>
                                        <small class="text-muted">Create an invoice with the selected lab test costs</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('walk-ins.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-clipboard-plus"></i> Create Lab Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cost Summary Sidebar -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-calculator"></i> Cost Summary</h6>
                </div>
                <div class="card-body">
                    <div id="costSummary">
                        <div class="text-center text-muted">
                            <i class="bi bi-clipboard-data fs-1"></i>
                            <p class="mt-2">Select lab tests to see cost breakdown</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAllTests()">
                            <i class="bi bi-check-all"></i> Select All
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAllTests()">
                            <i class="bi bi-x-circle"></i> Clear All
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="selectCommonTests()">
                            <i class="bi bi-star"></i> Common Tests
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.test-template-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.test-template-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.test-template-card.selected {
    border-color: #007bff;
    background-color: #f8f9fa;
}

.cost-badge {
    font-size: 0.8rem;
}

#costSummary .cost-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

#costSummary .cost-total {
    font-weight: bold;
    font-size: 1.1rem;
    color: #007bff;
    border-top: 2px solid #007bff;
    margin-top: 0.5rem;
    padding-top: 0.5rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.test-template-checkbox');
    const costSummary = document.getElementById('costSummary');
    
    // Add click handlers to template cards
    document.querySelectorAll('.test-template-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox') {
                const checkbox = this.querySelector('.test-template-checkbox');
                checkbox.checked = !checkbox.checked;
                updateCardSelection(this, checkbox.checked);
                updateCostSummary();
            }
        });
    });
    
    // Add change handlers to checkboxes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const card = this.closest('.test-template-card');
            updateCardSelection(card, this.checked);
            updateCostSummary();
        });
    });
    
    function updateCardSelection(card, isSelected) {
        if (isSelected) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    }
    
    function updateCostSummary() {
        const selectedTests = Array.from(checkboxes).filter(cb => cb.checked);
        
        if (selectedTests.length === 0) {
            costSummary.innerHTML = `
                <div class="text-center text-muted">
                    <i class="bi bi-clipboard-data fs-1"></i>
                    <p class="mt-2">Select lab tests to see cost breakdown</p>
                </div>
            `;
            return;
        }
        
        let totalCost = 0;
        let costItems = '';
        
        selectedTests.forEach(checkbox => {
            const cost = parseFloat(checkbox.dataset.cost) || 0;
            totalCost += cost;
            const card = checkbox.closest('.test-template-card');
            const testName = card.querySelector('strong').textContent;
            
            costItems += `
                <div class="cost-item">
                    <span>${testName}</span>
                    <span>GHS ${cost.toFixed(2)}</span>
                </div>
            `;
        });
        
        costSummary.innerHTML = `
            ${costItems}
            <div class="cost-total">
                <span>Total Cost</span>
                <span>GHS ${totalCost.toFixed(2)}</span>
            </div>
        `;
    }
    
    // Quick action functions
    window.selectAllTests = function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
            const card = checkbox.closest('.test-template-card');
            updateCardSelection(card, true);
        });
        updateCostSummary();
    };
    
    window.clearAllTests = function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
            const card = checkbox.closest('.test-template-card');
            updateCardSelection(card, false);
        });
        updateCostSummary();
    };
    
    window.selectCommonTests = function() {
        clearAllTests();
        // Select common tests (you can customize this list)
        const commonTestNames = ['Complete Blood Count', 'Blood Glucose', 'Urinalysis'];
        checkboxes.forEach(checkbox => {
            const card = checkbox.closest('.test-template-card');
            const testName = card.querySelector('strong').textContent;
            if (commonTestNames.some(name => testName.includes(name))) {
                checkbox.checked = true;
                updateCardSelection(card, true);
            }
        });
        updateCostSummary();
    };
    
    // Form validation
    document.getElementById('labRequestForm').addEventListener('submit', function(e) {
        const selectedTests = Array.from(checkboxes).filter(cb => cb.checked);
        if (selectedTests.length === 0) {
            e.preventDefault();
            alert('Please select at least one lab test.');
            return false;
        }
    });
});
</script>
@endsection
