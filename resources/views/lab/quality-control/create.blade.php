@extends('layouts.app')

@section('title', 'New Quality Control Record')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-1" style="color: #1e3a5f;">
            <i class="bi bi-clipboard-check"></i> New Quality Control Record
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('lab.quality-control.index') }}">Quality Control</a></li>
                <li class="breadcrumb-item active">New Record</li>
            </ol>
        </nav>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('lab.quality-control.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Test Parameter <span class="text-danger">*</span></label>
                        <select name="parameter_id" class="form-select @error('parameter_id') is-invalid @enderror" required>
                            <option value="">Select Parameter</option>
                            @foreach($parameters as $param)
                                <option value="{{ $param->id }}" {{ old('parameter_id') == $param->id ? 'selected' : '' }}>
                                    {{ $param->parameter_name }} - {{ $param->template->template_name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('parameter_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">QC Type <span class="text-danger">*</span></label>
                        <select name="qc_type" class="form-select @error('qc_type') is-invalid @enderror" required>
                            <option value="internal" {{ old('qc_type') == 'internal' ? 'selected' : '' }}>Internal QC</option>
                            <option value="external" {{ old('qc_type') == 'external' ? 'selected' : '' }}>External QC</option>
                        </select>
                        @error('qc_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">QC Level <span class="text-danger">*</span></label>
                        <select name="qc_level" class="form-select @error('qc_level') is-invalid @enderror" required>
                            <option value="level_1" {{ old('qc_level') == 'level_1' ? 'selected' : '' }}>Level 1</option>
                            <option value="level_2" {{ old('qc_level') == 'level_2' ? 'selected' : '' }}>Level 2</option>
                            <option value="level_3" {{ old('qc_level') == 'level_3' ? 'selected' : '' }}>Level 3</option>
                        </select>
                        @error('qc_level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">QC Material <span class="text-danger">*</span></label>
                        <input type="text" name="qc_material" class="form-control @error('qc_material') is-invalid @enderror" 
                               value="{{ old('qc_material') }}" required placeholder="e.g., Control Serum">
                        @error('qc_material')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Lot Number <span class="text-danger">*</span></label>
                        <input type="text" name="lot_number" class="form-control @error('lot_number') is-invalid @enderror" 
                               value="{{ old('lot_number') }}" required>
                        @error('lot_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
                        <input type="date" name="expiry_date" class="form-control @error('expiry_date') is-invalid @enderror" 
                               value="{{ old('expiry_date') }}" required>
                        @error('expiry_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Target Value <span class="text-danger">*</span></label>
                        <input type="number" step="0.0001" name="target_value" 
                               class="form-control @error('target_value') is-invalid @enderror" 
                               value="{{ old('target_value') }}" required>
                        @error('target_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Measured Value <span class="text-danger">*</span></label>
                        <input type="number" step="0.0001" name="measured_value" 
                               class="form-control @error('measured_value') is-invalid @enderror" 
                               value="{{ old('measured_value') }}" required>
                        @error('measured_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Acceptable Range (Low) <span class="text-danger">*</span></label>
                        <input type="number" step="0.0001" name="acceptable_range_low" 
                               class="form-control @error('acceptable_range_low') is-invalid @enderror" 
                               value="{{ old('acceptable_range_low') }}" required>
                        @error('acceptable_range_low')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Acceptable Range (High) <span class="text-danger">*</span></label>
                        <input type="number" step="0.0001" name="acceptable_range_high" 
                               class="form-control @error('acceptable_range_high') is-invalid @enderror" 
                               value="{{ old('acceptable_range_high') }}" required>
                        @error('acceptable_range_high')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" 
                              rows="3" placeholder="Any additional notes or observations">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save QC Record
                    </button>
                    <a href="{{ route('lab.quality-control.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
