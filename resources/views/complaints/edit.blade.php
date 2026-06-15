@extends('layouts.app')

@section('title', 'Edit Complaint')

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <!-- Toolbar -->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                    Edit Complaint - {{ $complaint->complaint_number }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-400 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('complaints.index') }}" class="text-muted text-hover-primary">Complaints</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-400 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Edit</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            
            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> Please fix the following errors:
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Update Complaint Information</h3>
                </div>

                <form action="{{ route('complaints.update', $complaint) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="card-body">
                        <div class="row">
                            <!-- Complainant Information -->
                            <div class="col-md-12">
                                <h4 class="mb-4">Complainant Information</h4>
                            </div>

                            <div class="col-md-6 mb-5">
                                <label class="form-label required">Complainant Type</label>
                                <select name="complainant_type" id="complainant_type" class="form-select form-select-solid" required>
                                    <option value="patient" {{ old('complainant_type', $complaint->complainant_type) == 'patient' ? 'selected' : '' }}>Patient/Customer</option>
                                    <option value="visitor" {{ old('complainant_type', $complaint->complainant_type) == 'visitor' ? 'selected' : '' }}>Visitor</option>
                                    <option value="staff" {{ old('complainant_type', $complaint->complainant_type) == 'staff' ? 'selected' : '' }}>Staff Member</option>
                                    <option value="other" {{ old('complainant_type', $complaint->complainant_type) == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-5" id="patient-select-container">
                                <label class="form-label">Select Patient (Optional)</label>
                                <select name="patient_id" id="patient_id" class="form-select form-select-solid">
                                    <option value="">Select Patient</option>
                                    @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}" data-name="{{ $patient->firstname }} {{ $patient->lastname }}" data-phone="{{ $patient->phone }}" data-email="{{ $patient->email }}" {{ old('patient_id', $complaint->patient_id) == $patient->id ? 'selected' : '' }}>
                                        {{ $patient->patient_id }} - {{ $patient->firstname }} {{ $patient->lastname }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-5">
                                <label class="form-label required">Complainant Name</label>
                                <input type="text" name="complainant_name" id="complainant_name" class="form-control form-control-solid" value="{{ old('complainant_name', $complaint->complainant_name) }}" required />
                            </div>

                            <div class="col-md-6 mb-5">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="complainant_phone" id="complainant_phone" class="form-control form-control-solid" value="{{ old('complainant_phone', $complaint->complainant_phone) }}" />
                            </div>

                            <div class="col-md-6 mb-5">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="complainant_email" id="complainant_email" class="form-control form-control-solid" value="{{ old('complainant_email', $complaint->complainant_email) }}" />
                            </div>

                            <!-- Complaint Details -->
                            <div class="col-md-12 mt-5">
                                <h4 class="mb-4">Complaint Details</h4>
                            </div>

                            <div class="col-md-12 mb-5">
                                <label class="form-label required">Subject</label>
                                <input type="text" name="subject" class="form-control form-control-solid" value="{{ old('subject', $complaint->subject) }}" required />
                            </div>

                            <div class="col-md-12 mb-5">
                                <label class="form-label required">Description</label>
                                <textarea name="description" class="form-control form-control-solid" rows="5" required>{{ old('description', $complaint->description) }}</textarea>
                            </div>

                            <div class="col-md-6 mb-5">
                                <label class="form-label required">Category</label>
                                <select name="category" class="form-select form-select-solid" required>
                                    <option value="service_quality" {{ old('category', $complaint->category) == 'service_quality' ? 'selected' : '' }}>Service Quality</option>
                                    <option value="staff_behavior" {{ old('category', $complaint->category) == 'staff_behavior' ? 'selected' : '' }}>Staff Behavior</option>
                                    <option value="wait_time" {{ old('category', $complaint->category) == 'wait_time' ? 'selected' : '' }}>Wait Time</option>
                                    <option value="billing" {{ old('category', $complaint->category) == 'billing' ? 'selected' : '' }}>Billing Issues</option>
                                    <option value="cleanliness" {{ old('category', $complaint->category) == 'cleanliness' ? 'selected' : '' }}>Cleanliness</option>
                                    <option value="medical_care" {{ old('category', $complaint->category) == 'medical_care' ? 'selected' : '' }}>Medical Care</option>
                                    <option value="facilities" {{ old('category', $complaint->category) == 'facilities' ? 'selected' : '' }}>Facilities</option>
                                    <option value="other" {{ old('category', $complaint->category) == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-5">
                                <label class="form-label required">Severity</label>
                                <select name="severity" class="form-select form-select-solid" required>
                                    <option value="low" {{ old('severity', $complaint->severity) == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('severity', $complaint->severity) == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('severity', $complaint->severity) == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="critical" {{ old('severity', $complaint->severity) == 'critical' ? 'selected' : '' }}>Critical</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-5">
                                <label class="form-label required">Status</label>
                                <select name="status" class="form-select form-select-solid" required>
                                    <option value="pending" {{ old('status', $complaint->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="under_review" {{ old('status', $complaint->status) == 'under_review' ? 'selected' : '' }}>Under Review</option>
                                    <option value="investigating" {{ old('status', $complaint->status) == 'investigating' ? 'selected' : '' }}>Investigating</option>
                                    <option value="resolved" {{ old('status', $complaint->status) == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="closed" {{ old('status', $complaint->status) == 'closed' ? 'selected' : '' }}>Closed</option>
                                    <option value="rejected" {{ old('status', $complaint->status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-5">
                                <label class="form-label required">Priority</label>
                                <select name="priority" class="form-select form-select-solid" required>
                                    <option value="low" {{ old('priority', $complaint->priority) == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="normal" {{ old('priority', $complaint->priority) == 'normal' ? 'selected' : '' }}>Normal</option>
                                    <option value="high" {{ old('priority', $complaint->priority) == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ old('priority', $complaint->priority) == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-5">
                                <label class="form-label">Assign To (Staff)</label>
                                <select name="assigned_to" class="form-select form-select-solid">
                                    <option value="">Not Assigned</option>
                                    @foreach($staff as $staffMember)
                                    <option value="{{ $staffMember->id }}" {{ old('assigned_to', $complaint->assigned_to) == $staffMember->id ? 'selected' : '' }}>
                                        {{ $staffMember->firstname }} {{ $staffMember->lastname }}
                                        @if($staffMember->roles->first())
                                            ({{ $staffMember->roles->first()->name }})
                                        @endif
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Response and Resolution -->
                            <div class="col-md-12 mt-5">
                                <h4 class="mb-4">Response & Resolution</h4>
                            </div>

                            <div class="col-md-12 mb-5">
                                <label class="form-label">Response</label>
                                <textarea name="response" class="form-control form-control-solid" rows="4">{{ old('response', $complaint->response) }}</textarea>
                            </div>

                            <div class="col-md-12 mb-5">
                                <label class="form-label">Resolution Notes</label>
                                <textarea name="resolution_notes" class="form-control form-control-solid" rows="4">{{ old('resolution_notes', $complaint->resolution_notes) }}</textarea>
                            </div>

                            <!-- Follow-up -->
                            <div class="col-md-12 mt-5">
                                <h4 class="mb-4">Follow-up</h4>
                            </div>

                            <div class="col-md-6 mb-5">
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="requires_follow_up" id="requires_follow_up" value="1" {{ old('requires_follow_up', $complaint->requires_follow_up) ? 'checked' : '' }} />
                                    <label class="form-check-label" for="requires_follow_up">
                                        Requires Follow-up
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-5" id="follow-up-date-container">
                                <label class="form-label">Follow-up Date</label>
                                <input type="date" name="follow_up_date" class="form-control form-control-solid" value="{{ old('follow_up_date', $complaint->follow_up_date ? $complaint->follow_up_date->format('Y-m-d') : '') }}" />
                            </div>

                            <div class="col-md-12 mb-5">
                                <label class="form-label">Follow-up Notes</label>
                                <textarea name="follow_up_notes" class="form-control form-control-solid" rows="3">{{ old('follow_up_notes', $complaint->follow_up_notes) }}</textarea>
                            </div>

                            <!-- Attachments -->
                            <div class="col-md-12 mb-5">
                                <label class="form-label">Add More Attachments (Optional)</label>
                                <input type="file" name="attachments[]" class="form-control form-control-solid" multiple accept="image/*,.pdf,.doc,.docx" />
                                <div class="form-text">You can upload additional images, PDFs, or documents (Max 10MB per file)</div>
                                
                                @if($complaint->attachments)
                                <div class="mt-3">
                                    <strong>Existing Attachments:</strong>
                                    <ul class="list-unstyled mt-2">
                                        @foreach($complaint->attachments as $attachment)
                                        <li class="mb-1">
                                            <i class="bi bi-paperclip"></i> {{ $attachment['name'] ?? 'Attachment' }}
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </div>

                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end">
                        <a href="{{ route('complaints.index') }}" class="btn btn-light me-3">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle fs-4"></i> Update Complaint
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const complainantType = document.getElementById('complainant_type');
    const patientSelectContainer = document.getElementById('patient-select-container');
    const patientSelect = document.getElementById('patient_id');
    const complainantName = document.getElementById('complainant_name');
    const complainantPhone = document.getElementById('complainant_phone');
    const complainantEmail = document.getElementById('complainant_email');
    const requiresFollowUp = document.getElementById('requires_follow_up');
    const followUpDateContainer = document.getElementById('follow-up-date-container');

    // Show/hide patient select based on complainant type
    complainantType.addEventListener('change', function() {
        if (this.value === 'patient') {
            patientSelectContainer.style.display = 'block';
        } else {
            patientSelectContainer.style.display = 'none';
            patientSelect.value = '';
        }
    });

    // Auto-fill complainant details when patient is selected
    patientSelect.addEventListener('change', function() {
        if (this.value) {
            const selectedOption = this.options[this.selectedIndex];
            complainantName.value = selectedOption.dataset.name || '';
            complainantPhone.value = selectedOption.dataset.phone || '';
            complainantEmail.value = selectedOption.dataset.email || '';
        }
    });

    // Show/hide follow-up date based on checkbox
    requiresFollowUp.addEventListener('change', function() {
        followUpDateContainer.style.display = this.checked ? 'block' : 'none';
    });

    // Initial state
    if (complainantType.value === 'patient') {
        patientSelectContainer.style.display = 'block';
    }
    if (requiresFollowUp.checked) {
        followUpDateContainer.style.display = 'block';
    }
});
</script>
@endpush
@endsection

