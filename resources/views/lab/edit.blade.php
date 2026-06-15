@extends('layouts.app')

@section('title', 'Edit Lab Request')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1" style="color: #1e3a5f;">Edit Lab Request</h1><p class="text-secondary mb-0">{{ $lab->request_number }}</p></div>
        <a href="{{ route('lab.show', $lab) }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Cancel</a>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('lab.update', $lab) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="test_category" class="form-label">Test Category</label>
                                <select class="form-select @error('test_category') is-invalid @enderror" id="test_category" name="test_category">
                                    <option value="">Select Category</option>
                                    @foreach(['Hematology', 'Biochemistry', 'Microbiology', 'Serology', 'Urine Analysis', 'Other'] as $cat)
                                    <option value="{{ $cat }}" {{ old('test_category', $lab->test_category) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                                @error('test_category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                    @foreach(['pending', 'in_progress', 'completed', 'cancelled'] as $status)
                                    <option value="{{ $status }}" {{ old('status', $lab->status) == $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="template_id" class="form-label">Test Template</label>
                                <select class="form-select @error('template_id') is-invalid @enderror" id="template_id" name="template_id">
                                    <option value="">-- Select Template --</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}" {{ old('template_id', $lab->template_id) == $template->id ? 'selected' : '' }}>
                                            {{ $template->template_name }} ({{ ucfirst($template->template_type) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('template_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Assign a template to enable results entry for this lab request</small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('lab.show', $lab) }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
