@extends('layouts.app')

@section('title', 'Teleconsultation Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Teleconsultation Details</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('teleconsultations.index') }}">Teleconsultations</a></li>
                    <li class="breadcrumb-item active">Details</li>
                </ol>
            </nav>
                </div>
        <div class="d-flex gap-2">
            <a href="{{ route('teleconsultations.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                    @can('teleconsultation.edit')
            <a href="{{ route('teleconsultations.edit', $teleconsultation) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
                    </a>
                    @endcan
        </div>
    </div>

    <!-- Content -->
            <div class="row">
                <!-- Left Column - Details -->
        <div class="col-lg-4 mb-4">
                    <!-- Teleconsultation Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Teleconsultation Information</h5>
                        </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="fw-bold text-dark"><i class="bi bi-calendar-event me-2"></i>Scheduled Time</label>
                        <p class="text-muted mb-0">{{ $teleconsultation->scheduled_at->format('M d, Y h:i A') }}</p>
                                </div>

                    <hr>

                    <div class="mb-3">
                        <label class="fw-bold text-dark"><i class="bi bi-person me-2"></i>Patient</label>
                                        @if($teleconsultation->patient)
                            <p class="text-muted mb-0">
                                <a href="{{ route('patients.show', $teleconsultation->patient) }}" class="text-primary">
                                    {{ $teleconsultation->patient->first_name }} {{ $teleconsultation->patient->last_name }}
                                </a>
                                <br>
                                <small>{{ $teleconsultation->patient->phone }}</small>
                            </p>
                                        @else
                            <p class="text-danger mb-0">Patient Not Found</p>
                                        @endif
                                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="fw-bold text-dark"><i class="bi bi-person-badge me-2"></i>Doctor</label>
                        <p class="text-muted mb-0">
                            Dr. {{ $teleconsultation->doctor->first_name }} {{ $teleconsultation->doctor->last_name }}
                            <br>
                            <small>{{ $teleconsultation->doctor->email }}</small>
                        </p>
                                </div>

                    <hr>

                    <div class="mb-3">
                        <label class="fw-bold text-dark"><i class="bi bi-camera-video me-2"></i>Type</label>
                        <p class="mb-0">
                            @php
                                $typeColors = [
                                    'video' => 'primary',
                                    'audio' => 'success',
                                    'chat' => 'info'
                                ];
                            @endphp
                            <span class="badge bg-{{ $typeColors[$teleconsultation->consultation_type] ?? 'secondary' }}">
                                <i class="bi bi-{{ $teleconsultation->consultation_type === 'video' ? 'camera-video' : ($teleconsultation->consultation_type === 'audio' ? 'mic' : 'chat') }}"></i>
                                            {{ ucfirst($teleconsultation->consultation_type) }}
                                        </span>
                        </p>
                                </div>

                    <hr>

                    <div class="mb-3">
                        <label class="fw-bold text-dark"><i class="bi bi-info-circle me-2"></i>Status</label>
                        <p class="mb-0">
                                        @php
                                            $statusColors = [
                                                'scheduled' => 'warning',
                                                'waiting' => 'info',
                                                'in_progress' => 'primary',
                                                'completed' => 'success',
                                                'cancelled' => 'danger',
                                                'failed' => 'dark'
                                            ];
                                        @endphp
                            <span class="badge bg-{{ $statusColors[$teleconsultation->status] ?? 'secondary' }}">
                                            {{ ucfirst(str_replace('_', ' ', $teleconsultation->status)) }}
                                        </span>
                        </p>
                                </div>

                                @if($teleconsultation->duration_minutes)
                    <hr>
                    <div class="mb-3">
                        <label class="fw-bold text-dark"><i class="bi bi-clock me-2"></i>Duration</label>
                        <p class="text-muted mb-0">{{ $teleconsultation->duration_minutes }} minutes</p>
                                </div>
                                @endif
                        </div>
                    </div>

                    <!-- Meeting Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Meeting Information</h5>
                        </div>
                <div class="card-body">
                            @if($teleconsultation->meeting_url)
                    <div class="mb-3">
                                <label class="form-label fw-bold">Meeting Link</label>
                                <div class="input-group">
                            <input type="text" class="form-control form-control-sm" value="{{ $teleconsultation->meeting_url }}" readonly>
                            <button class="btn btn-outline-primary btn-sm" onclick="copyToClipboard('{{ $teleconsultation->meeting_url }}')">
                                <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>

                    <div class="mb-3">
                                <label class="form-label fw-bold">Meeting Code</label>
                                <div class="input-group">
                            <input type="text" class="form-control form-control-sm text-center fw-bold" value="{{ $teleconsultation->meeting_id }}" readonly>
                            <button class="btn btn-outline-primary btn-sm" onclick="copyToClipboard('{{ $teleconsultation->meeting_id }}')">
                                <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>

                            @if($teleconsultation->meeting_password)
                    <div class="mb-0">
                                <label class="form-label fw-bold">Meeting Password</label>
                                <div class="input-group">
                            <input type="text" class="form-control form-control-sm text-center fw-bold" value="{{ $teleconsultation->meeting_password }}" readonly>
                            <button class="btn btn-outline-primary btn-sm" onclick="copyToClipboard('{{ $teleconsultation->meeting_password }}')">
                                <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                            @endif
                            @else
                    <div class="text-center py-4">
                        <i class="bi bi-info-circle text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-2 mb-0">No meeting information available</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Actions</h5>
                        </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                                @if($teleconsultation->status == 'scheduled')
                            <button class="btn btn-success" onclick="startTeleconsultation()">
                                <i class="bi bi-play-circle"></i> Start Teleconsultation
                                    </button>
                                @elseif($teleconsultation->status == 'in_progress')
                                    <button class="btn btn-danger" onclick="endTeleconsultation()">
                                <i class="bi bi-stop-circle"></i> End Teleconsultation
                                    </button>
                                @endif

                        @if($teleconsultation->status == 'scheduled' || $teleconsultation->status == 'waiting')
                                    <button class="btn btn-warning" onclick="cancelTeleconsultation()">
                                <i class="bi bi-x-circle"></i> Cancel Teleconsultation
                                    </button>
                                @endif

                        @if(!$teleconsultation->patient_consent_given && ($teleconsultation->status == 'scheduled' || $teleconsultation->status == 'waiting'))
                                    <button class="btn btn-info" onclick="giveConsent()">
                                <i class="bi bi-check-circle"></i> Give Patient Consent
                                    </button>
                                @endif

                        @can('teleconsultation.edit')
                        <a href="{{ route('teleconsultations.edit', $teleconsultation) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit Teleconsultation
                                </a>
                        @endcan
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Video Call -->
        <div class="col-lg-8 mb-4">
                    <!-- Jitsi Video Call -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 text-dark">Video Call Session</h5>
                        <small class="text-muted">Session with {{ $teleconsultation->patient ? $teleconsultation->patient->first_name . ' ' . $teleconsultation->patient->last_name : 'Unknown Patient' }}</small>
                    </div>
                    <div>
                        <span id="call-status" class="badge bg-warning">
                            <i class="bi bi-clock me-1"></i> Ready to Join
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    @if($teleconsultation->meeting_url)
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar-event fs-3 text-primary me-3"></i>
                                <div>
                                    <h6 class="mb-1 fw-bold">Scheduled Time</h6>
                                    <span class="text-muted">{{ $teleconsultation->scheduled_at->format('M d, Y h:i A') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-badge fs-3 text-success me-3"></i>
                                        <div>
                                    <h6 class="mb-1 fw-bold">Doctor</h6>
                                            <span class="text-muted">Dr. {{ $teleconsultation->doctor->first_name }} {{ $teleconsultation->doctor->last_name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <button class="btn btn-primary" onclick="joinJitsiMeeting()">
                            <i class="bi bi-camera-video me-2"></i>Join Meeting
                                </button>
                                
                        <button class="btn btn-outline-primary" onclick="sendInvite()">
                            <i class="bi bi-share me-2"></i>Send Invite
                                </button>
                            </div>

                            <!-- Jitsi Meet Container -->
                    <div id="jitsi-container" style="height: 600px; width: 100%; display: none;" class="border rounded bg-dark"></div>

                            <!-- Mobile Instructions -->
                            <div id="mobile-instructions" class="alert alert-info" style="display: none;">
                        <i class="bi bi-info-circle me-2"></i>
                                <div>
                            <h6 class="fw-bold">Mobile Instructions</h6>
                                    <p class="mb-0">This will open Jitsi Meet in your mobile browser or app. Make sure you have a stable internet connection for the best experience.</p>
                                </div>
                            </div>
                            @else
                    <div class="text-center py-5">
                        <i class="bi bi-x-circle text-danger" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">No Meeting Available</h4>
                                <p class="text-muted">This teleconsultation does not have a meeting link configured.</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Consultation Notes -->
                    @if($teleconsultation->consultation_notes)
                    <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-dark">Consultation Notes</h5>
                        </div>
                <div class="card-body">
                    <div class="text-dark">
                                {!! nl2br(e($teleconsultation->consultation_notes)) !!}
                            </div>
                        </div>
                    </div>
                    @endif
        </div>
    </div>
</div>

<!-- Jitsi Meet Script -->
<script src="{{ $jitsiConfig['server_url'] ?? 'https://meet.jit.si' }}/external_api.js"></script>

<script>
let jitsiApi = null;
let callStartTime = null;
let callDurationInterval = null;

// Jitsi configuration
const jitsiConfig = @json($jitsiConfig);

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show toast notification
        showToast('Copied to clipboard!', 'success');
    }, function(err) {
        console.error('Could not copy text: ', err);
        showToast('Failed to copy to clipboard', 'error');
    });
}

function joinJitsiMeeting() {
    if (!jitsiConfig.enabled) {
        showToast('Jitsi Meet is not enabled', 'error');
        return;
    }

    const isMobile = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isMobile) {
        // For mobile, open in new window
        window.open('{{ $teleconsultation->meeting_url }}', '_blank');
        document.getElementById('mobile-instructions').style.display = 'block';
        updateCallStatus('active');
        startCallTimer();
    } else {
        // For desktop, embed Jitsi Meet
        startJitsiMeeting();
    }
}

function startJitsiMeeting() {
    const domain = jitsiConfig.server_url.replace('https://', '').replace('http://', '');
    const options = {
        roomName: '{{ $teleconsultation->meeting_id }}',
        width: '100%',
        height: '100%',
        parentNode: document.getElementById('jitsi-container'),
        configOverwrite: {
            ...jitsiConfig.config_overwrite,
            startWithAudioMuted: jitsiConfig.mute_on_entry || false,
            startWithVideoMuted: false,
            enableWelcomePage: true,
            enableClosePage: true,
            enablePrejoinPage: true,
        },
        interfaceConfigOverwrite: {
            ...jitsiConfig.interface_config,
            SHOW_JITSI_WATERMARK: false,
            SHOW_WATERMARK_FOR_GUESTS: false,
            SHOW_POWERED_BY: false,
            PROVIDER_NAME: '{{ $hospitalBranding['name'] ?? 'Hospital' }}',
            APP_NAME: '{{ $hospitalBranding['name'] ?? 'Hospital' }} Teleconsultation',
        },
        userInfo: {
            displayName: 'Dr. {{ $teleconsultation->doctor->first_name }} {{ $teleconsultation->doctor->last_name }}',
            email: '{{ $teleconsultation->doctor->email }}',
        },
    };

    try {
        jitsiApi = new JitsiMeetExternalAPI(domain, options);
        
        // Show the container
        document.getElementById('jitsi-container').style.display = 'block';
        
        // Set up event listeners
        jitsiApi.addListener('videoConferenceJoined', function() {
            updateCallStatus('active');
            startCallTimer();
            showToast('Joined Jitsi Meet session successfully', 'success');
        });

        jitsiApi.addListener('videoConferenceLeft', function() {
            updateCallStatus('ended');
            stopCallTimer();
            endTeleconsultation();
        });

        jitsiApi.addListener('readyToClose', function() {
            updateCallStatus('ended');
            stopCallTimer();
            endTeleconsultation();
        });

        jitsiApi.addListener('errorOccurred', function(error) {
            console.error('Jitsi error:', error);
            showToast('An error occurred during the meeting', 'error');
        });

    } catch (error) {
        console.error('Failed to start Jitsi meeting:', error);
        showToast('Failed to start video meeting', 'error');
    }
}

function updateCallStatus(status) {
    const statusElement = document.getElementById('call-status');
    const statusConfig = {
        'ready': { class: 'bg-warning', text: 'Ready to Join', icon: 'bi-clock' },
        'active': { class: 'bg-success', text: 'Active', icon: 'bi-check-circle' },
        'ended': { class: 'bg-secondary', text: 'Ended', icon: 'bi-x-circle' }
    };
    
    const config = statusConfig[status] || statusConfig['ready'];
    statusElement.className = `badge ${config.class}`;
    statusElement.innerHTML = `<i class="bi ${config.icon} me-1"></i>${config.text}`;
}

function startCallTimer() {
    callStartTime = new Date();
    callDurationInterval = setInterval(updateCallDuration, 1000);
}

function stopCallTimer() {
    if (callDurationInterval) {
        clearInterval(callDurationInterval);
        callDurationInterval = null;
    }
}

function updateCallDuration() {
    if (callStartTime) {
        const now = new Date();
        const duration = Math.floor((now - callStartTime) / 1000);
        const hours = Math.floor(duration / 3600);
        const minutes = Math.floor((duration % 3600) / 60);
        const seconds = duration % 60;
        
        const timeString = hours > 0 
            ? `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
            : `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
        const statusElement = document.getElementById('call-status');
        statusElement.innerHTML = `<i class="bi bi-check-circle me-1"></i>Active - ${timeString}`;
    }
}

function sendInvite() {
    const meetingUrl = '{{ $teleconsultation->meeting_url }}';
    copyToClipboard(meetingUrl);
    showToast('Meeting link copied to clipboard', 'success');
}

function startTeleconsultation() {
    fetch('{{ route("teleconsultations.start", $teleconsultation) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Teleconsultation started successfully', 'success');
            location.reload();
        } else {
            showToast(data.message || 'Failed to start teleconsultation', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to start teleconsultation', 'error');
    });
}

function endTeleconsultation() {
    fetch('{{ route("teleconsultations.end", $teleconsultation) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Teleconsultation ended successfully', 'success');
            location.reload();
        } else {
            showToast(data.message || 'Failed to end teleconsultation', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to end teleconsultation', 'error');
    });
}

function cancelTeleconsultation() {
    if (confirm('Are you sure you want to cancel this teleconsultation?')) {
        fetch('{{ route("teleconsultations.cancel", $teleconsultation) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Teleconsultation cancelled successfully', 'success');
                location.reload();
            } else {
                showToast(data.message || 'Failed to cancel teleconsultation', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Failed to cancel teleconsultation', 'error');
        });
    }
}

function giveConsent() {
    fetch('{{ route("teleconsultations.consent", $teleconsultation) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Consent given successfully', 'success');
            location.reload();
        } else {
            showToast(data.message || 'Failed to give consent', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to give consent', 'error');
    });
}

function showToast(message, type = 'info') {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info-circle'} fs-4 me-3"></i>
            <span>${message}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (jitsiApi) {
        jitsiApi.dispose();
    }
    stopCallTimer();
});
</script>
@endsection
