@extends('layouts.app')

@section('title', 'Complaint Details')

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <!-- Toolbar -->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                    Complaint Details - {{ $complaint->complaint_number }}
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
                    <li class="breadcrumb-item text-muted">Details</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                @can('edit_complaints')
                <a href="{{ route('complaints.edit', $complaint) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil fs-4"></i> Edit
                </a>
                @endcan
                <a href="{{ route('complaints.index') }}" class="btn btn-sm btn-light">
                    <i class="bi bi-arrow-left fs-4"></i> Back
                </a>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            
            <div class="row g-5">
                <!-- Left Column - Main Details -->
                <div class="col-xl-8">
                    <!-- Complaint Information -->
                    <div class="card mb-5">
                        <div class="card-header">
                            <h3 class="card-title">Complaint Information</h3>
                            <div class="card-toolbar">
                                @if($complaint->status === 'pending')
                                    <span class="badge badge-warning fs-7">Pending</span>
                                @elseif($complaint->status === 'under_review')
                                    <span class="badge badge-primary fs-7">Under Review</span>
                                @elseif($complaint->status === 'investigating')
                                    <span class="badge badge-info fs-7">Investigating</span>
                                @elseif($complaint->status === 'resolved')
                                    <span class="badge badge-success fs-7">Resolved</span>
                                @elseif($complaint->status === 'closed')
                                    <span class="badge badge-secondary fs-7">Closed</span>
                                @else
                                    <span class="badge badge-danger fs-7">Rejected</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-bold text-muted">Complaint Number</label>
                                <div class="col-lg-8">
                                    <span class="fw-bold fs-6 text-gray-800">{{ $complaint->complaint_number }}</span>
                                </div>
                            </div>

                            <div class="row mb-7">
                                <label class="col-lg-4 fw-bold text-muted">Subject</label>
                                <div class="col-lg-8">
                                    <span class="fw-bold fs-6 text-gray-800">{{ $complaint->subject }}</span>
                                </div>
                            </div>

                            <div class="row mb-7">
                                <label class="col-lg-4 fw-bold text-muted">Description</label>
                                <div class="col-lg-8">
                                    <span class="fw-semibold fs-6 text-gray-600">{{ $complaint->description }}</span>
                                </div>
                            </div>

                            <div class="row mb-7">
                                <label class="col-lg-4 fw-bold text-muted">Category</label>
                                <div class="col-lg-8">
                                    <span class="badge badge-light-info">{{ ucfirst(str_replace('_', ' ', $complaint->category)) }}</span>
                                </div>
                            </div>

                            <div class="row mb-7">
                                <label class="col-lg-4 fw-bold text-muted">Severity</label>
                                <div class="col-lg-8">
                                    @if($complaint->severity === 'critical')
                                        <span class="badge badge-danger">Critical</span>
                                    @elseif($complaint->severity === 'high')
                                        <span class="badge badge-warning">High</span>
                                    @elseif($complaint->severity === 'medium')
                                        <span class="badge badge-info">Medium</span>
                                    @else
                                        <span class="badge badge-light">Low</span>
                                    @endif
                                </div>
                            </div>

                            <div class="row mb-7">
                                <label class="col-lg-4 fw-bold text-muted">Priority</label>
                                <div class="col-lg-8">
                                    @if($complaint->priority === 'urgent')
                                        <span class="badge badge-danger">Urgent</span>
                                    @elseif($complaint->priority === 'high')
                                        <span class="badge badge-warning">High</span>
                                    @elseif($complaint->priority === 'normal')
                                        <span class="badge badge-info">Normal</span>
                                    @else
                                        <span class="badge badge-light">Low</span>
                                    @endif
                                </div>
                            </div>

                            <div class="row mb-7">
                                <label class="col-lg-4 fw-bold text-muted">Filed Date</label>
                                <div class="col-lg-8">
                                    <span class="fw-semibold fs-6 text-gray-600">{{ $complaint->created_at->format('d M Y, h:i A') }}</span>
                                </div>
                            </div>

                            @if($complaint->branch)
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-bold text-muted">Branch</label>
                                <div class="col-lg-8">
                                    <span class="fw-semibold fs-6 text-gray-600">{{ $complaint->branch->name }}</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Complainant Information -->
                    <div class="card mb-5">
                        <div class="card-header">
                            <h3 class="card-title">Complainant Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-bold text-muted">Type</label>
                                <div class="col-lg-8">
                                    <span class="badge badge-light-primary">{{ ucfirst($complaint->complainant_type) }}</span>
                                </div>
                            </div>

                            <div class="row mb-7">
                                <label class="col-lg-4 fw-bold text-muted">Name</label>
                                <div class="col-lg-8">
                                    <span class="fw-bold fs-6 text-gray-800">{{ $complaint->complainant_name }}</span>
                                </div>
                            </div>

                            @if($complaint->complainant_phone)
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-bold text-muted">Phone</label>
                                <div class="col-lg-8">
                                    <span class="fw-semibold fs-6 text-gray-600">{{ $complaint->complainant_phone }}</span>
                                </div>
                            </div>
                            @endif

                            @if($complaint->complainant_email)
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-bold text-muted">Email</label>
                                <div class="col-lg-8">
                                    <span class="fw-semibold fs-6 text-gray-600">{{ $complaint->complainant_email }}</span>
                                </div>
                            </div>
                            @endif

                            @if($complaint->patient)
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-bold text-muted">Patient Record</label>
                                <div class="col-lg-8">
                                    <a href="{{ route('patients.show', $complaint->patient) }}" class="fw-bold text-primary">
                                        {{ $complaint->patient->patient_id }} - {{ $complaint->patient->firstname }} {{ $complaint->patient->lastname }}
                                    </a>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Response & Resolution -->
                    @if($complaint->response || $complaint->resolution_notes || $complaint->status === 'resolved')
                    <div class="card mb-5">
                        <div class="card-header">
                            <h3 class="card-title">Response & Resolution</h3>
                        </div>
                        <div class="card-body">
                            @if($complaint->response)
                            <div class="mb-7">
                                <label class="fw-bold text-muted mb-2">Response</label>
                                <div class="p-5 bg-light-info rounded">
                                    <p class="mb-0 text-gray-700">{{ $complaint->response }}</p>
                                </div>
                            </div>
                            @endif

                            @if($complaint->resolution_notes)
                            <div class="mb-7">
                                <label class="fw-bold text-muted mb-2">Resolution Notes</label>
                                <div class="p-5 bg-light-success rounded">
                                    <p class="mb-0 text-gray-700">{{ $complaint->resolution_notes }}</p>
                                </div>
                            </div>
                            @endif

                            @if($complaint->resolved_at)
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-bold text-muted">Resolved Date</label>
                                <div class="col-lg-8">
                                    <span class="fw-semibold fs-6 text-gray-600">{{ $complaint->resolved_at->format('d M Y, h:i A') }}</span>
                                </div>
                            </div>
                            @endif

                            @if($complaint->resolvedByUser)
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-bold text-muted">Resolved By</label>
                                <div class="col-lg-8">
                                    <span class="fw-semibold fs-6 text-gray-600">{{ $complaint->resolvedByUser->firstname }} {{ $complaint->resolvedByUser->lastname }}</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Attachments -->
                    @if($complaint->attachments && count($complaint->attachments) > 0)
                    <div class="card mb-5">
                        <div class="card-header">
                            <h3 class="card-title">Attachments</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                @foreach($complaint->attachments as $attachment)
                                <div class="col-md-6">
                                    <div class="border border-gray-300 border-dashed rounded p-4">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-paperclip fs-2x text-primary me-3"></i>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-gray-800">{{ $attachment['name'] ?? 'Attachment' }}</div>
                                                <div class="text-muted fs-7">{{ isset($attachment['size']) ? number_format($attachment['size'] / 1024, 2) . ' KB' : '' }}</div>
                                            </div>
                                            @if(isset($attachment['path']))
                                            <a href="{{ asset('storage/' . $attachment['path']) }}" target="_blank" class="btn btn-sm btn-icon btn-light-primary">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Right Column - Status & Actions -->
                <div class="col-xl-4">
                    <!-- Assignment -->
                    <div class="card mb-5">
                        <div class="card-header">
                            <h3 class="card-title">Assignment</h3>
                        </div>
                        <div class="card-body">
                            @if($complaint->assignedUser)
                            <div class="d-flex align-items-center mb-5">
                                <div class="symbol symbol-45px me-3">
                                    <div class="symbol-label fs-3 bg-light-primary text-primary">
                                        {{ substr($complaint->assignedUser->firstname, 0, 1) }}
                                    </div>
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-gray-800">{{ $complaint->assignedUser->firstname }} {{ $complaint->assignedUser->lastname }}</span>
                                    <span class="text-muted fs-7">
                                        @if($complaint->assignedUser->roles->first())
                                            {{ $complaint->assignedUser->roles->first()->name }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                            @else
                            <div class="text-center py-5">
                                <i class="bi bi-person-x fs-3x text-muted mb-3"></i>
                                <div class="text-muted">Not assigned yet</div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Follow-up -->
                    @if($complaint->requires_follow_up)
                    <div class="card mb-5">
                        <div class="card-header">
                            <h3 class="card-title">Follow-up</h3>
                        </div>
                        <div class="card-body">
                            @if($complaint->follow_up_date)
                            <div class="mb-5">
                                <label class="fw-bold text-muted mb-2">Follow-up Date</label>
                                <div class="fw-bold fs-6 text-gray-800">{{ $complaint->follow_up_date->format('d M Y') }}</div>
                            </div>
                            @endif

                            @if($complaint->follow_up_notes)
                            <div>
                                <label class="fw-bold text-muted mb-2">Notes</label>
                                <p class="text-gray-600">{{ $complaint->follow_up_notes }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Activity Log -->
                    <div class="card mb-5">
                        <div class="card-header">
                            <h3 class="card-title">Activity Log</h3>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item mb-5">
                                    <div class="timeline-line w-40px"></div>
                                    <div class="timeline-icon symbol symbol-circle symbol-40px">
                                        <div class="symbol-label bg-light-success">
                                            <i class="bi bi-plus-circle fs-2 text-success"></i>
                                        </div>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="fw-bold text-gray-800">Complaint Filed</div>
                                        <div class="text-muted fs-7">{{ $complaint->created_at->format('d M Y, h:i A') }}</div>
                                        @if($complaint->creator)
                                        <div class="text-muted fs-7">by {{ $complaint->creator->firstname }} {{ $complaint->creator->lastname }}</div>
                                        @endif
                                    </div>
                                </div>

                                @if($complaint->updated_at && $complaint->updated_at != $complaint->created_at)
                                <div class="timeline-item mb-5">
                                    <div class="timeline-line w-40px"></div>
                                    <div class="timeline-icon symbol symbol-circle symbol-40px">
                                        <div class="symbol-label bg-light-info">
                                            <i class="bi bi-pencil fs-2 text-info"></i>
                                        </div>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="fw-bold text-gray-800">Last Updated</div>
                                        <div class="text-muted fs-7">{{ $complaint->updated_at->format('d M Y, h:i A') }}</div>
                                        @if($complaint->updater)
                                        <div class="text-muted fs-7">by {{ $complaint->updater->firstname }} {{ $complaint->updater->lastname }}</div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                @if($complaint->resolved_at)
                                <div class="timeline-item">
                                    <div class="timeline-line w-40px"></div>
                                    <div class="timeline-icon symbol symbol-circle symbol-40px">
                                        <div class="symbol-label bg-light-success">
                                            <i class="bi bi-check-circle fs-2 text-success"></i>
                                        </div>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="fw-bold text-gray-800">Resolved</div>
                                        <div class="text-muted fs-7">{{ $complaint->resolved_at->format('d M Y, h:i A') }}</div>
                                        @if($complaint->resolvedByUser)
                                        <div class="text-muted fs-7">by {{ $complaint->resolvedByUser->firstname }} {{ $complaint->resolvedByUser->lastname }}</div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

