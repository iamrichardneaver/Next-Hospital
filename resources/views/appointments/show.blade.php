@extends('layouts.app')

@section('title', 'Appointment Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Appointment Details</h1>
            <p class="text-secondary mb-0">{{ $appointment->appointment_number }}</p>
        </div>
        <div>
            <a href="{{ route('appointments.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            @can('edit_appointments')
            <a href="{{ route('appointments.edit', $appointment) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endcan
            @can('delete_appointments')
            <form action="{{ route('appointments.destroy', $appointment) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this appointment?')">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </form>
            @endcan
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Appointment Info</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Appointment #:</strong><br><span class="badge bg-primary">{{ $appointment->appointment_number }}</span></p>
                    <p class="mb-2"><strong>Date:</strong><br>{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('l, M d, Y') }}</p>
                    <p class="mb-2"><strong>Time:</strong><br>{{ $appointment->appointment_time }}</p>
                    <p class="mb-2"><strong>Type:</strong><br><span class="badge bg-info">{{ ucfirst($appointment->appointment_type) }}</span></p>
                    <p class="mb-2"><strong>Status:</strong><br>
                        <span class="badge bg-{{ $appointment->status === 'completed' ? 'success' : ($appointment->status === 'scheduled' ? 'primary' : ($appointment->status === 'cancelled' ? 'danger' : 'secondary')) }}">
                            {{ ucfirst($appointment->status) }}
                        </span>
                    </p>
                    <p class="mb-0"><strong>Branch:</strong><br>{{ $appointment->branch->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Patient Information</h5>
                </div>
                <div class="card-body">
                    @if($appointment->patient)
                    <div class="text-center mb-3">
                        <div class="user-avatar bg-success mx-auto" style="width: 80px; height: 80px; font-size: 2rem; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                            {{ strtoupper(substr($appointment->patient->first_name, 0, 1)) }}{{ strtoupper(substr($appointment->patient->last_name, 0, 1)) }}
                        </div>
                    </div>
                    <h5 class="text-center mb-3">{{ $appointment->patient->full_name }}</h5>
                    <p class="mb-2"><strong>Patient ID:</strong> {{ $appointment->patient->patient_number }}</p>
                    <p class="mb-2"><strong>Age:</strong> {{ $appointment->patient->age }} years</p>
                    <p class="mb-2"><strong>Gender:</strong> {{ $appointment->patient->gender }}</p>
                    <p class="mb-2"><strong>Phone:</strong> {{ $appointment->patient->phone ?? 'N/A' }}</p>
                    <p class="mb-0"><strong>Email:</strong> {{ $appointment->patient->email ?? 'N/A' }}</p>
                    <div class="mt-3">
                        <a href="{{ route('patients.show', $appointment->patient) }}" class="btn btn-sm btn-outline-success w-100">
                            <i class="bi bi-eye"></i> View Patient Profile
                        </a>
                    </div>
                    @else
                    <p class="text-secondary">No patient information available</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-person-badge"></i> Doctor Information</h5>
                </div>
                <div class="card-body">
                    @if($appointment->doctor)
                    <div class="text-center mb-3">
                        <div class="user-avatar bg-info mx-auto" style="width: 80px; height: 80px; font-size: 2rem; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                            {{ strtoupper(substr($appointment->doctor->first_name ?? 'D', 0, 1)) }}{{ strtoupper(substr($appointment->doctor->last_name ?? 'R', 0, 1)) }}
                        </div>
                    </div>
                    <h5 class="text-center mb-3">Dr. {{ $appointment->doctor->first_name }} {{ $appointment->doctor->last_name }}</h5>
                    <p class="mb-2"><strong>Specialization:</strong> {{ $appointment->doctor->staffProfile->specialization ?? 'General Practice' }}</p>
                    <p class="mb-2"><strong>Department:</strong> {{ $appointment->doctor->staffProfile->department->name ?? 'N/A' }}</p>
                    <p class="mb-0"><strong>License:</strong> {{ $appointment->doctor->staffProfile->license_number ?? 'N/A' }}</p>
                    @else
                    <p class="text-secondary">No doctor assigned</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($appointment->reason)
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-file-text"></i> Reason for Visit</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $appointment->reason }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(auth()->user()?->hasRole('patient') && $appointment->status === 'completed' && $appointment->doctor)
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-star"></i> Rate Your Doctor</h5>
                </div>
                <div class="card-body">
                    @if($appointment->doctorReview)
                        <p class="mb-2"><strong>Your rating:</strong>
                            @for($i = 1; $i <= 5; $i++)
                                <i class="bi bi-star{{ $i <= $appointment->doctorReview->rating ? '-fill text-warning' : '' }}"></i>
                            @endfor
                            ({{ $appointment->doctorReview->rating }}/5)
                        </p>
                        @if($appointment->doctorReview->comment)
                            <p class="mb-0 text-secondary">{{ $appointment->doctorReview->comment }}</p>
                        @endif
                    @else
                        <form action="{{ route('appointments.review', $appointment) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="rating" class="form-label">Rating</label>
                                <select name="rating" id="rating" class="form-select @error('rating') is-invalid @enderror" required>
                                    <option value="">Select rating</option>
                                    @for($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}" @selected(old('rating') == $i)>{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                                    @endfor
                                </select>
                                @error('rating')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="comment" class="form-label">Comment (optional)</label>
                                <textarea name="comment" id="comment" rows="3" class="form-control @error('comment') is-invalid @enderror" maxlength="2000">{{ old('comment') }}</textarea>
                                @error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Submit Review
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Payment Information Section -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm border-{{ $appointment->billing_status === 'paid' ? 'success' : 'warning' }}">
                <div class="card-header bg-{{ $appointment->billing_status === 'paid' ? 'success' : 'warning' }} text-white">
                    <h5 class="mb-0"><i class="bi bi-credit-card"></i> Payment Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p class="mb-2"><strong>Payment Status:</strong>
                                <span class="badge bg-{{ $appointment->billing_status === 'paid' ? 'success' : ($appointment->billing_status === 'billed' ? 'info' : 'warning') }}">
                                    {{ ucfirst($appointment->billing_status) }}
                                </span>
                            </p>
                            <div id="fee-breakdown">
                                <p class="text-muted mb-2"><i class="bi bi-hourglass-split"></i> Loading fee information...</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            @if($appointment->billing_status !== 'paid')
                            <ul class="nav nav-pills nav-fill mb-3" role="tablist">
                                <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#apt-pay-paystack" type="button">Paystack</button></li>
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#apt-pay-staff" type="button">Cash / MoMo</button></li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="apt-pay-paystack">
                                    <form action="{{ route('appointments.initialize-payment', $appointment) }}" method="POST" id="payment-form">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="payment_email" class="form-label small">Email for Payment</label>
                                            <input type="email" class="form-control form-control-sm" id="payment_email" name="email"
                                                   value="{{ $appointment->patient->email ?? ($appointment->patient->user->email ?? '') }}" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100" id="pay-button">
                                            <i class="bi bi-credit-card"></i> Pay via Paystack
                                        </button>
                                    </form>
                                </div>
                                @can('process_payments')
                                <div class="tab-pane fade" id="apt-pay-staff">
                                    <form action="{{ route('appointments.record-staff-payment', $appointment) }}" method="POST">
                                        @csrf
                                        @include('partials.payment-method-fields', ['idPrefix' => 'apt_staff', 'showPaystack' => false, 'required' => true])
                                        <div class="mb-3">
                                            <label class="form-label small">Notes</label>
                                            <input type="text" class="form-control form-control-sm" name="notes" placeholder="Optional">
                                        </div>
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="bi bi-cash-coin"></i> Record Payment
                                        </button>
                                    </form>
                                </div>
                                @endcan
                            </div>
                            @else
                            <div class="alert alert-success mb-0">
                                <i class="bi bi-check-circle"></i> Payment Completed
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($appointment->appointment_type === 'teleconsultation' && $appointment->is_teleconsultation)
    <!-- Virtual Meeting Section -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-camera-video"></i> Virtual Consultation</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <h6><i class="bi bi-link-45deg"></i> Meeting Details</h6>
                                <p class="mb-2"><strong>Meeting URL:</strong></p>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="meetingUrl" value="{{ $appointment->meeting_url ?? 'Generating...' }}" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyMeetingUrl()" {{ !$appointment->meeting_url ? 'disabled' : '' }}>
                                        <i class="bi bi-clipboard"></i> Copy
                                    </button>
                                </div>
                                @if($appointment->meeting_password)
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <strong>Meeting Password:</strong> {{ $appointment->meeting_password }}
                                    </small>
                                </div>
                                @endif
                            </div>
                            
                            <div class="mb-3">
                                <h6><i class="bi bi-clock"></i> Meeting Instructions</h6>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-check-circle text-success"></i> Click "Join Meeting" to start the virtual consultation</li>
                                    <li><i class="bi bi-check-circle text-success"></i> Ensure you have a stable internet connection</li>
                                    <li><i class="bi bi-check-circle text-success"></i> Use headphones for better audio quality</li>
                                    <li><i class="bi bi-check-circle text-success"></i> Test your camera and microphone before joining</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="mb-3">
                                    <i class="bi bi-camera-video-fill text-primary" style="font-size: 3rem;"></i>
                                </div>
                                <h6>Ready to Join?</h6>
                                <p class="text-muted small mb-3">Click the button below to start your virtual consultation</p>
                                
                                <button class="btn btn-primary btn-lg mb-2 w-100" onclick="joinVirtualMeeting()" {{ !$appointment->meeting_url ? 'disabled' : '' }}>
                                    <i class="bi bi-play-circle"></i> {{ $appointment->meeting_url ? 'Join Meeting' : 'Generating Meeting...' }}
                                </button>
                                
                                <button class="btn btn-outline-primary w-100" onclick="sendMeetingInvite()">
                                    <i class="bi bi-share"></i> Send Invite
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Jitsi Meet Container -->
                    <div id="jitsi-container" style="height: 500px; width: 100%; display: none;" class="border rounded mt-3"></div>
                    
                    <!-- Mobile Instructions -->
                    <div id="mobile-instructions" class="alert alert-info mt-3" style="display: none;">
                        <i class="bi bi-info-circle"></i>
                        <strong>Mobile Users:</strong> The meeting will open in a new window. Make sure you have a stable internet connection for the best experience.
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Audit Trail</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th width="200">Created:</th>
                            <td>{{ $appointment->created_at->format('M d, Y h:i A') }} by {{ $appointment->creator->first_name ?? 'System' }} {{ $appointment->creator->last_name ?? '' }}</td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td>{{ $appointment->updated_at->format('M d, Y h:i A') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if($appointment->appointment_type === 'teleconsultation' && $appointment->is_teleconsultation)
<!-- Jitsi Meet External API -->
<script src='https://meet.jit.si/external_api.js'></script>
@endif

<script>
// Load fee information
document.addEventListener('DOMContentLoaded', function() {
    loadFeeInformation();
});

function loadFeeInformation() {
    const appointmentId = {{ $appointment->id }};
    const doctorId = {{ $appointment->doctor_id }};
    const branchId = {{ $appointment->branch_id }};
    const appointmentType = '{{ $appointment->appointment_type }}';
    
    // Use dynamic API endpoint (relative URL works in all environments)
    const apiUrl = window.appConfig ? window.appConfig.api('appointment-fees/calculate') : '/api/appointment-fees/calculate';
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            doctor_id: doctorId,
            branch_id: branchId,
            appointment_type: appointmentType
        })
    })
        .then(response => response.json())
        .then(data => {
            const feeDiv = document.getElementById('fee-breakdown');
            
            if (data.success && data.data && data.data.breakdown) {
                const breakdown = data.data.breakdown;
                feeDiv.innerHTML = `
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td>Base Fee:</td>
                            <td class="text-end">${breakdown.currency} ${parseFloat(breakdown.base_fee).toFixed(2)}</td>
                        </tr>
                        ${breakdown.platform_fee > 0 ? `
                        <tr>
                            <td>Platform Fee:</td>
                            <td class="text-end">${breakdown.currency} ${parseFloat(breakdown.platform_fee).toFixed(2)}</td>
                        </tr>
                        ` : ''}
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-end">${breakdown.currency} ${parseFloat(breakdown.subtotal).toFixed(2)}</td>
                        </tr>
                        ${breakdown.tax_amount > 0 ? `
                        <tr>
                            <td>Tax (${breakdown.tax_rate}%):</td>
                            <td class="text-end">${breakdown.currency} ${parseFloat(breakdown.tax_amount).toFixed(2)}</td>
                        </tr>
                        ` : ''}
                        <tr class="fw-bold border-top">
                            <td>Total Fee:</td>
                            <td class="text-end text-primary">${breakdown.currency} ${parseFloat(breakdown.total).toFixed(2)}</td>
                        </tr>
                    </table>
                `;
            } else {
                feeDiv.innerHTML = '<p class="text-muted mb-0">Fee information not available. Please contact admin to set up appointment fees.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading fee information:', error);
            document.getElementById('fee-breakdown').innerHTML = '<p class="text-muted mb-0">Unable to load fee information</p>';
        });
}

@if($appointment->appointment_type === 'teleconsultation' && $appointment->is_teleconsultation)
<script>
let jitsiApi = null;

// Jitsi configuration
const jitsiConfig = {
    enabled: true,
    server_url: 'https://meet.jit.si',
    config_overwrite: {
        startWithAudioMuted: false,
        startWithVideoMuted: false,
        enableWelcomePage: true,
        enableClosePage: true,
        enablePrejoinPage: true,
        disableModeratorIndicator: true,
        startScreenSharing: false,
        enableEmailInStats: false,
    },
    interface_config: {
        SHOW_JITSI_WATERMARK: false,
        SHOW_WATERMARK_FOR_GUESTS: false,
        SHOW_POWERED_BY: false,
        PROVIDER_NAME: '{{ $hospitalBranding['name'] ?? 'Hospital' }}',
        APP_NAME: '{{ $hospitalBranding['name'] ?? 'Hospital' }} Teleconsultation',
        TOOLBAR_BUTTONS: [
            'microphone', 'camera', 'closedcaptions', 'desktop', 'embedmeeting',
            'fullscreen', 'fodeviceselection', 'hangup', 'profile', 'chat', 'recording',
            'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand',
            'videoquality', 'filmstrip', 'invite', 'feedback', 'stats', 'shortcuts',
            'tileview', 'videobackgroundblur', 'download', 'help', 'mute-everyone', 'e2ee'
        ],
    },
    mute_on_entry: false
};

function copyMeetingUrl() {
    const meetingUrl = document.getElementById('meetingUrl');
    meetingUrl.select();
    meetingUrl.setSelectionRange(0, 99999); // For mobile devices
    
    navigator.clipboard.writeText(meetingUrl.value).then(() => {
        showToast('Meeting URL copied to clipboard!', 'success');
    }).catch(() => {
        // Fallback for older browsers
        document.execCommand('copy');
        showToast('Meeting URL copied to clipboard!', 'success');
    });
}

function joinVirtualMeeting() {
    if (!jitsiConfig.enabled) {
        showToast('Virtual meeting is not available', 'error');
        return;
    }

    const meetingUrl = '{{ $appointment->meeting_url }}';
    if (!meetingUrl || meetingUrl === 'Generating...') {
        showToast('Meeting is being prepared. Please wait a moment and refresh the page.', 'warning');
        return;
    }

    const isMobile = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isMobile) {
        // For mobile, open in new window
        window.open(meetingUrl, '_blank');
        document.getElementById('mobile-instructions').style.display = 'block';
    } else {
        // For desktop, embed Jitsi Meet
        startJitsiMeeting();
    }
}

function startJitsiMeeting() {
    const meetingUrl = '{{ $appointment->meeting_url }}';
    const domain = jitsiConfig.server_url.replace('https://', '').replace('http://', '');
    const roomName = meetingUrl.split('/').pop();
    
    const options = {
        roomName: roomName,
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
            displayName: '{{ auth()->user()->first_name ?? "User" }} {{ auth()->user()->last_name ?? "" }}',
            email: '{{ auth()->user()->email ?? "" }}',
        },
    };

    try {
        jitsiApi = new JitsiMeetExternalAPI(domain, options);
        
        // Show the container
        document.getElementById('jitsi-container').style.display = 'block';
        
        // Scroll to the meeting
        document.getElementById('jitsi-container').scrollIntoView({ behavior: 'smooth' });
        
        // Handle meeting events
        jitsiApi.addEventListener('videoConferenceJoined', function() {
            console.log('Joined the meeting');
            showToast('Successfully joined the virtual consultation!', 'success');
        });
        
        jitsiApi.addEventListener('videoConferenceLeft', function() {
            console.log('Left the meeting');
            document.getElementById('jitsi-container').style.display = 'none';
        });
        
        jitsiApi.addEventListener('readyToClose', function() {
            console.log('Ready to close');
            document.getElementById('jitsi-container').style.display = 'none';
        });
        
    } catch (error) {
        console.error('Error starting Jitsi meeting:', error);
        showToast('Failed to start the virtual meeting. Please try again.', 'error');
    }
}

function sendMeetingInvite() {
    const meetingUrl = '{{ $appointment->meeting_url }}';
    const patientName = '{{ $appointment->patient->full_name ?? "Patient" }}';
    const doctorName = 'Dr. {{ $appointment->doctor->first_name ?? "" }} {{ $appointment->doctor->last_name ?? "" }}';
    const appointmentDate = '{{ \Carbon\Carbon::parse($appointment->appointment_date)->format("M d, Y") }}';
    const appointmentTime = '{{ $appointment->appointment_time }}';
    
    const message = `Virtual Consultation Invitation

Dear ${patientName},

You have a virtual consultation scheduled with ${doctorName}.

Appointment Details:
- Date: ${appointmentDate}
- Time: ${appointmentTime}
- Meeting URL: ${meetingUrl}

Please click the link above to join your virtual consultation.

Best regards,
{{ $hospitalBranding['name'] ?? 'Hospital' }} Team`;

    // Try to open email client
    const mailtoLink = `mailto:?subject=Virtual Consultation Invitation - {{ $hospitalBranding['name'] ?? 'Hospital' }}&body=${encodeURIComponent(message)}`;
    window.open(mailtoLink);
    
    showToast('Meeting invite email opened!', 'success');
}

// Toast notification function
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    // Add to toast container
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.appendChild(toast);
    
    // Show toast
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}
</script>
@endif
@endsection
