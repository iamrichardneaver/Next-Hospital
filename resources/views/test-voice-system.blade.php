@extends('layouts.app')

@section('title', 'Voice System Test')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-mic-fill me-2"></i>
                        Voice System Test & Configuration
                    </h5>
                </div>
                <div class="card-body">
                    
                    <!-- Voice Preference Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="voicePreference" class="form-label">Voice Preference</label>
                            <select class="form-select" id="voicePreference">
                                <option value="male">Male Voice (Clearer)</option>
                                <option value="female">Female Voice</option>
                                <option value="african-female">African Female Voice (Clear & Fast)</option>
                                <option value="ghanaian-female">Ghanaian Female Voice (Clear & Loud)</option>
                            </select>
                            <small class="text-muted">Choose the voice type for all system announcements</small>
                        </div>
                        <div class="col-md-6">
                            <label for="testText" class="form-label">Test Text</label>
                            <input type="text" class="form-control" id="testText" value="Attention please. This is a test of the voice system. Please listen carefully.">
                        </div>
                    </div>
                    
                    <!-- Test Buttons -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>Test Different Voice Types:</h6>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-primary" onclick="testVoice('male')">
                                    <i class="bi bi-person-voice me-1"></i>
                                    Test Male Voice
                                </button>
                                <button type="button" class="btn btn-success" onclick="testVoice('female')">
                                    <i class="bi bi-person-voice me-1"></i>
                                    Test Female Voice
                                </button>
                                <button type="button" class="btn btn-info" onclick="testVoice('african-female')">
                                    <i class="bi bi-person-voice me-1"></i>
                                    Test African Female Voice
                                </button>
                                <button type="button" class="btn btn-warning" onclick="testVoice('ghanaian-female')">
                                    <i class="bi bi-person-voice me-1"></i>
                                    Test Ghanaian Female Voice
                                </button>
                                <button type="button" class="btn btn-warning" onclick="testCurrentVoice()">
                                    <i class="bi bi-play-circle me-1"></i>
                                    Test Current Voice
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Integration Tests -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>System Integration Tests:</h6>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-primary" onclick="testWorkflowNotification()">
                                    <i class="bi bi-bell me-1"></i>
                                    Test Workflow Notification
                                </button>
                                <button type="button" class="btn btn-outline-success" onclick="testQueueAnnouncement()">
                                    <i class="bi bi-megaphone me-1"></i>
                                    Test Queue Announcement
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Voice Information -->
                    <div class="row">
                        <div class="col-12">
                            <h6>Current Voice Information:</h6>
                            <div class="alert alert-info">
                                <div id="voiceInfo">
                                    <strong>Selected Voice:</strong> <span id="currentVoiceName">Loading...</span><br>
                                    <strong>Voice Type:</strong> <span id="currentVoiceType">Loading...</span><br>
                                    <strong>Language:</strong> <span id="currentVoiceLang">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Available Voices -->
                    <div class="row">
                        <div class="col-12">
                            <h6>Available Voices:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Language</th>
                                            <th>Default</th>
                                            <th>Local Service</th>
                                        </tr>
                                    </thead>
                                    <tbody id="voicesTable">
                                        <tr>
                                            <td colspan="4" class="text-center">Loading voices...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const voicePreferenceSelect = document.getElementById('voicePreference');
    
    // Load current voice preference
    const currentPreference = localStorage.getItem('voicePreference') || 'male';
    voicePreferenceSelect.value = currentPreference;
    
    // Update voice preference when changed
    voicePreferenceSelect.addEventListener('change', (e) => {
        const preference = e.target.value;
        if (window.voiceConfig) {
            window.voiceConfig.setVoicePreference(preference);
            updateVoiceInfo();
        }
    });
    
    // Initialize voice info
    setTimeout(updateVoiceInfo, 1000);
    setTimeout(loadAvailableVoices, 1000);
});

function testVoice(preference) {
    if (window.voiceConfig) {
        window.voiceConfig.setVoicePreference(preference);
        let testText = document.getElementById('testText').value;
        
        // Use specific test text for different voice types
        if (preference === 'african-female') {
            testText = 'Attention please. This is the African female voice test. Notice the clarity and speed. Please proceed to your designated area.';
        } else if (preference === 'ghanaian-female') {
            testText = 'Good morning! This is the Ghanaian female voice test. Notice the clarity and warmth. Please come to the reception area. Thank you very much!';
        }
        
        window.voiceConfig.testVoice(testText);
        updateVoiceInfo();
    } else {
        alert('Voice configuration not loaded. Please refresh the page.');
    }
}

function testCurrentVoice() {
    if (window.voiceConfig) {
        const testText = document.getElementById('testText').value;
        window.voiceConfig.testVoice(testText);
    } else {
        alert('Voice configuration not loaded. Please refresh the page.');
    }
}

function testWorkflowNotification() {
    if (window.workflowNotificationService) {
        window.workflowNotificationService.playHumanLikeSpeech('standard', 0.8);
    } else {
        alert('Workflow notification service not available.');
    }
}

function testQueueAnnouncement() {
    if (window.queueAudio) {
        window.queueAudio.announcePatient('A001', 'John Doe', 'OPD', 'Room 3');
    } else {
        alert('Queue audio service not available.');
    }
}

function updateVoiceInfo() {
    if (window.voiceConfig && window.voiceConfig.currentVoice) {
        const voice = window.voiceConfig.currentVoice;
        document.getElementById('currentVoiceName').textContent = voice.name || 'Unknown';
        document.getElementById('currentVoiceType').textContent = window.voiceConfig.getVoicePreference();
        document.getElementById('currentVoiceLang').textContent = voice.lang || 'Unknown';
    }
}

function loadAvailableVoices() {
    if (window.voiceConfig) {
        const voices = window.voiceConfig.getAvailableVoices();
        const tbody = document.getElementById('voicesTable');
        
        if (voices.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">No voices available</td></tr>';
            return;
        }
        
        tbody.innerHTML = voices.map(voice => `
            <tr>
                <td>${voice.name}</td>
                <td>${voice.lang}</td>
                <td>${voice.default ? 'Yes' : 'No'}</td>
                <td>${voice.localService ? 'Yes' : 'No'}</td>
            </tr>
        `).join('');
    }
}
</script>
@endpush
