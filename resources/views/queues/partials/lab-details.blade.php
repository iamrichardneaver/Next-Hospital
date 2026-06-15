<div class="lab-details" data-queue-id="{{ $queue->id }}">
    <!-- Patient Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h6 class="text-primary mb-3"><i class="bi bi-person"></i> Patient Information</h6>
            <div class="card">
                <div class="card-body">
                    <p><strong>Name:</strong> {{ $queue->patient->first_name }} {{ $queue->patient->last_name }}</p>
                    <p><strong>Patient Number:</strong> <span class="badge bg-primary">{{ $queue->patient->patient_number }}</span></p>
                    <p><strong>Gender:</strong> {{ $queue->patient->gender }}</p>
                    <p><strong>Phone:</strong> {{ $queue->patient->phone ?? 'N/A' }}</p>
                    @if($queue->patient->date_of_birth)
                        <p><strong>Age:</strong> {{ \Carbon\Carbon::parse($queue->patient->date_of_birth)->age }} years</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <h6 class="text-info mb-3"><i class="bi bi-clock"></i> Queue Information</h6>
            <div class="card">
                <div class="card-body">
                    <p><strong>Ticket Number:</strong> <span class="badge bg-secondary">{{ $queue->ticket_number }}</span></p>
                    <p><strong>Position:</strong> {{ $queue->position }}</p>
                    <p><strong>Priority:</strong> 
                        <span class="badge bg-{{ $queue->priority === 'critical' ? 'danger' : ($queue->priority === 'urgent' ? 'warning' : 'secondary') }}">
                            {{ ucfirst($queue->priority) }}
                        </span>
                    </p>
                    <p><strong>Queued At:</strong> {{ $queue->queued_at->format('M d, Y H:i') }}</p>
                    <p><strong>Wait Time:</strong> {{ $queue->queued_at->diffForHumans() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lab Requests -->
    @if($queue->labRequests && $queue->labRequests->count() > 0)
        <div class="row">
            <div class="col-12">
                <h6 class="text-success mb-3"><i class="bi bi-flask"></i> Lab Tests ({{ $queue->labRequests->count() }})</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Test Type</th>
                                <th>Description</th>
                                <th>Specimen</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Technician</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($queue->labRequests as $labRequest)
                                <tr>
                                    <td>
                                        <strong>{{ $labRequest->test_type ?? $labRequest->test_type_name ?? 'Lab Test' }}</strong>
                                        @if($labRequest->test_category_name)
                                            <br><small class="text-muted">{{ $labRequest->test_category_name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($labRequest->test_description)
                                            {{ Str::limit($labRequest->test_description, 50) }}
                                        @else
                                            <span class="text-muted">No description</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($labRequest->specimen_type)
                                            <span class="badge bg-info">{{ $labRequest->specimen_type }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $labRequest->priority === 'stat' ? 'danger' : ($labRequest->priority === 'urgent' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($labRequest->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $labRequest->status === 'completed' ? 'success' : ($labRequest->status === 'in_progress' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($labRequest->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($labRequest->technician)
                                            {{ $labRequest->technician->first_name }} {{ $labRequest->technician->last_name }}
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @if($labRequest->status === 'pending')
                                                <button class="btn btn-outline-primary btn-sm" onclick="startLabTest({{ $labRequest->id }})" title="Start Test">
                                                    <i class="bi bi-play-fill"></i>
                                                </button>
                                            @elseif($labRequest->status === 'in_progress')
                                                <button class="btn btn-outline-success btn-sm" onclick="completeLabTest({{ $labRequest->id }})" title="Complete Test">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-outline-info btn-sm" onclick="viewLabTestDetails({{ $labRequest->id }})" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> No lab requests found for this patient.
        </div>
    @endif

    <!-- Special Instructions -->
    @if($queue->notes)
        <div class="row mt-4">
            <div class="col-12">
                <h6 class="text-warning mb-3"><i class="bi bi-exclamation-circle"></i> Special Instructions</h6>
                <div class="alert alert-warning">
                    {{ $queue->notes }}
                </div>
            </div>
        </div>
    @endif
</div>
