@extends('layouts.app')

@section('title', 'Edit Prescription')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-pencil"></i> Edit Prescription #{{ $prescription->prescription_number }}
            </h1>
            <p class="text-secondary mb-0">Modify prescription details and medications</p>
        </div>
        <div>
            <a href="{{ route('pharmacy.prescriptions.show', $prescription) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Prescription
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form method="POST" action="{{ route('pharmacy.prescriptions.update', $prescription) }}">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- Prescription Information -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Prescription Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Prescription Number:</strong><br>
                            <span class="text-primary">{{ $prescription->prescription_number }}</span>
                        </div>
                        <div class="mb-3">
                            <strong>Date:</strong><br>
                            {{ $prescription->prescription_date->format('M d, Y') }}
                        </div>
                        <div class="mb-3">
                            <strong>Status:</strong><br>
                            @php
                                $statusClass = match($prescription->status) {
                                    'pending' => 'badge-warning',
                                    'dispensed' => 'badge-info',
                                    'completed' => 'badge-success',
                                    'cancelled' => 'badge-danger',
                                    default => 'badge-secondary'
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ ucfirst($prescription->status) }}</span>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Prescription Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4" 
                                      placeholder="Enter prescription notes...">{{ old('notes', $prescription->notes) }}</textarea>
                            @error('notes')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Patient Information -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person"></i> Patient Information</h5>
                    </div>
                    <div class="card-body">
                        @if($prescription->patient)
                            <div class="mb-2">
                                <strong>Name:</strong><br>
                                {{ $prescription->patient->first_name }} {{ $prescription->patient->last_name }}
                            </div>
                            <div class="mb-2">
                                <strong>Patient Number:</strong><br>
                                <span class="text-primary">{{ $prescription->patient->patient_number }}</span>
                            </div>
                            <div class="mb-2">
                                <strong>Phone:</strong><br>
                                {{ $prescription->patient->phone ?? 'N/A' }}
                            </div>
                        @else
                            <div class="mb-2">
                                <strong>Name:</strong><br>
                                <span class="text-muted">Unknown Patient</span>
                            </div>
                            <div class="mb-2">
                                <strong>Patient Number:</strong><br>
                                <span class="text-muted">N/A</span>
                            </div>
                            <div class="mb-2">
                                <strong>Phone:</strong><br>
                                <span class="text-muted">N/A</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Medications -->
            <div class="col-md-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-capsule-pill"></i> Medications</h5>
                        <button type="button" class="btn btn-primary btn-sm" id="addMedication">
                            <i class="bi bi-plus"></i> Add Medication
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="medications-container">
                            @foreach($prescription->orders as $index => $order)
                            <div class="medication-item border rounded p-3 mb-3" data-index="{{ $index }}">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Drug</label>
                                        <select class="form-select drug-select" name="orders[{{ $index }}][drug_id]" required>
                                            <option value="">Select Drug</option>
                                            @foreach($drugs as $drug)
                                            <option value="{{ $drug->id }}" 
                                                    data-strength="{{ $drug->strength }}"
                                                    data-unit="{{ $drug->unit }}"
                                                    {{ $order->drug_id == $drug->id ? 'selected' : '' }}>
                                                {{ $drug->name }}
                                                @if($drug->generic_name) - {{ $drug->generic_name }} @endif
                                            </option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="orders[{{ $index }}][id]" value="{{ $order->id }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" class="form-control" name="orders[{{ $index }}][quantity]" 
                                               value="{{ $order->quantity }}" min="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Actions</label>
                                        <div>
                                            <button type="button" class="btn btn-danger btn-sm remove-medication">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Dosage Instructions</label>
                                        <input type="text" class="form-control" name="orders[{{ $index }}][dosage_instructions]" 
                                               value="{{ $order->dosage_instructions }}" placeholder="e.g., 1 tablet">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Frequency</label>
                                        <select class="form-select" name="orders[{{ $index }}][frequency]">
                                            <option value="">Select Frequency</option>
                                            <option value="Once daily" {{ $order->frequency == 'Once daily' ? 'selected' : '' }}>Once daily</option>
                                            <option value="Twice daily" {{ $order->frequency == 'Twice daily' ? 'selected' : '' }}>Twice daily</option>
                                            <option value="Three times daily" {{ $order->frequency == 'Three times daily' ? 'selected' : '' }}>Three times daily</option>
                                            <option value="Four times daily" {{ $order->frequency == 'Four times daily' ? 'selected' : '' }}>Four times daily</option>
                                            <option value="As needed" {{ $order->frequency == 'As needed' ? 'selected' : '' }}>As needed</option>
                                            <option value="Every 4 hours" {{ $order->frequency == 'Every 4 hours' ? 'selected' : '' }}>Every 4 hours</option>
                                            <option value="Every 6 hours" {{ $order->frequency == 'Every 6 hours' ? 'selected' : '' }}>Every 6 hours</option>
                                            <option value="Every 8 hours" {{ $order->frequency == 'Every 8 hours' ? 'selected' : '' }}>Every 8 hours</option>
                                            <option value="Every 12 hours" {{ $order->frequency == 'Every 12 hours' ? 'selected' : '' }}>Every 12 hours</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Duration</label>
                                        <input type="text" class="form-control" name="orders[{{ $index }}][duration]" 
                                               value="{{ $order->duration }}" placeholder="e.g., 7 days, 2 weeks">
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        @if($prescription->orders->count() == 0)
                        <div class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <h5 class="mt-3 text-muted">No medications added</h5>
                            <p class="text-muted">Click "Add Medication" to add medications to this prescription.</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('pharmacy.prescriptions.show', $prescription) }}" class="btn btn-secondary">
                        <i class="bi bi-x"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Update Prescription
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let medicationIndex = {{ $prescription->orders->count() }};
    
    // Add medication
    document.getElementById('addMedication').addEventListener('click', function() {
        const container = document.getElementById('medications-container');
        const template = createMedicationTemplate(medicationIndex);
        container.insertAdjacentHTML('beforeend', template);
        medicationIndex++;
    });
    
    // Remove medication
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-medication')) {
            e.target.closest('.medication-item').remove();
        }
    });
    
    function createMedicationTemplate(index) {
        return `
            <div class="medication-item border rounded p-3 mb-3" data-index="${index}">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Drug</label>
                        <select class="form-select drug-select" name="orders[${index}][drug_id]" required>
                            <option value="">Select Drug</option>
                            @foreach($drugs as $drug)
                            <option value="{{ $drug->id }}" 
                                    data-strength="{{ $drug->strength }}"
                                    data-unit="{{ $drug->unit }}">
                                {{ $drug->name }}
                                @if($drug->generic_name) - {{ $drug->generic_name }} @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" name="orders[${index}][quantity]" 
                               value="1" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Actions</label>
                        <div>
                            <button type="button" class="btn btn-danger btn-sm remove-medication">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label class="form-label">Dosage Instructions</label>
                        <input type="text" class="form-control" name="orders[${index}][dosage_instructions]" 
                               placeholder="e.g., 1 tablet">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Frequency</label>
                        <select class="form-select" name="orders[${index}][frequency]">
                            <option value="">Select Frequency</option>
                            <option value="Once daily">Once daily</option>
                            <option value="Twice daily">Twice daily</option>
                            <option value="Three times daily">Three times daily</option>
                            <option value="Four times daily">Four times daily</option>
                            <option value="As needed">As needed</option>
                            <option value="Every 4 hours">Every 4 hours</option>
                            <option value="Every 6 hours">Every 6 hours</option>
                            <option value="Every 8 hours">Every 8 hours</option>
                            <option value="Every 12 hours">Every 12 hours</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Duration</label>
                        <input type="text" class="form-control" name="orders[${index}][duration]" 
                               placeholder="e.g., 7 days, 2 weeks">
                    </div>
                </div>
            </div>
        `;
    }
});
</script>
@endsection
