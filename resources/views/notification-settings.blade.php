@extends('layouts.app')

@section('title', 'Notification Settings')

@php
    $prefs = $preferences ?? [];
    $checked = fn (string $key, bool $default = false) => filter_var($prefs[$key] ?? $default, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '';
    $selected = fn (string $key, $value, $default = null) => (string) ($prefs[$key] ?? $default) === (string) $value ? 'selected' : '';
@endphp

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-bell-fill me-2"></i>
                        Workflow Notification Settings
                    </h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="testNotificationBtn">
                            <i class="bi bi-volume-up-fill me-1"></i>
                            Test Sound
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" id="testHumanSoundBtn">
                            <i class="bi bi-person-voice me-1"></i>
                            Test Human Voice
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="saveSettingsBtn">
                            <i class="bi bi-check-circle me-1"></i>
                            Save Settings
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    
                    <form id="notificationSettingsForm">
                        
                        <!-- General Audio Settings -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-volume-up me-2"></i>
                                    Audio Settings
                                </h6>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="audio_enabled" name="audio_enabled" {{ $checked('audio_enabled', true) }}>
                                    <label class="form-check-label" for="audio_enabled">
                                        <strong>Enable Audio Notifications</strong>
                                        <br><small class="text-muted">Play sounds when new work arrives</small>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="desktop_notification" name="desktop_notification" {{ $checked('desktop_notification', true) }}>
                                    <label class="form-check-label" for="desktop_notification">
                                        <strong>Desktop Notifications</strong>
                                        <br><small class="text-muted">Show browser notifications</small>
                                    </label>
                                </div>
                                <div class="mt-2" id="notificationPermissionStatus"></div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="requestPermissionBtn" style="display: none;">
                                    <i class="bi bi-bell me-1"></i>
                                    Enable Browser Notifications
                                </button>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="audio_volume" class="form-label">Volume Level</label>
                                <div class="d-flex align-items-center">
                                    <input type="range" class="form-range flex-grow-1 me-3" id="audio_volume" name="audio_volume" min="0" max="100" value="{{ $prefs['audio_volume'] ?? 80 }}">
                                    <span class="badge bg-primary" id="volumeDisplay">{{ $prefs['audio_volume'] ?? 80 }}%</span>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="voice_preference" class="form-label">Voice Preference</label>
                                <select class="form-select" id="voice_preference" name="voice_preference">
                                    <option value="male">Male Voice (Clearer)</option>
                                    <option value="female">Female Voice</option>
                                    <option value="african-female">African Female Voice (Clear & Fast)</option>
                                    <option value="ghanaian-female">Ghanaian Female Voice (Clear & Loud)</option>
                                </select>
                                <small class="text-muted">Choose the voice type for all system announcements</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="use_speech_synthesis" name="use_speech_synthesis">
                                    <label class="form-check-label" for="use_speech_synthesis">
                                        <strong>Human Voice Announcements</strong>
                                        <br><small class="text-muted">Use natural speech synthesis for notifications</small>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enhanced_audio" name="enhanced_audio">
                                    <label class="form-check-label" for="enhanced_audio">
                                        <strong>Enhanced Human-Like Audio</strong>
                                        <br><small class="text-muted">Use voice-like frequencies and natural patterns</small>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="check_interval" class="form-label">Check Interval (seconds)</label>
                                <select class="form-select" id="check_interval" name="check_interval">
                                    <option value="10" {{ $selected('check_interval', 10) }}>Every 10 seconds (Fast)</option>
                                    <option value="20" {{ $selected('check_interval', 20) }}>Every 20 seconds</option>
                                    <option value="30" {{ $selected('check_interval', 30, 30) }}>Every 30 seconds (Default)</option>
                                    <option value="60" {{ $selected('check_interval', 60) }}>Every 1 minute</option>
                                    <option value="120" {{ $selected('check_interval', 120) }}>Every 2 minutes</option>
                                    <option value="300" {{ $selected('check_interval', 300) }}>Every 5 minutes (Slow)</option>
                                </select>
                                <small class="text-muted">How often to check for new work items</small>
                            </div>
                        </div>
                        
                        <!-- Queue Type Notifications -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-list-task me-2"></i>
                                    Queue Types to Monitor
                                </h6>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify_opd_queue" name="notify_opd_queue" {{ $checked('notify_opd_queue', true) }}>
                                    <label class="form-check-label" for="notify_opd_queue">
                                        OPD Queue
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify_lab_queue" name="notify_lab_queue" {{ $checked('notify_lab_queue', true) }}>
                                    <label class="form-check-label" for="notify_lab_queue">
                                        Lab Queue
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify_pharmacy_queue" name="notify_pharmacy_queue" {{ $checked('notify_pharmacy_queue', true) }}>
                                    <label class="form-check-label" for="notify_pharmacy_queue">
                                        Pharmacy Queue
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify_emergency_queue" name="notify_emergency_queue" {{ $checked('notify_emergency_queue', true) }}>
                                    <label class="form-check-label" for="notify_emergency_queue">
                                        Emergency Queue
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify_triage_queue" name="notify_triage_queue" {{ $checked('notify_triage_queue', true) }}>
                                    <label class="form-check-label" for="notify_triage_queue">
                                        Triage Queue
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Priority Level Notifications -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Priority Levels to Notify
                                </h6>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify_routine" name="notify_routine" {{ $checked('notify_routine', true) }}>
                                    <label class="form-check-label" for="notify_routine">
                                        <span class="badge bg-primary">Routine</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify_urgent" name="notify_urgent" {{ $checked('notify_urgent', true) }}>
                                    <label class="form-check-label" for="notify_urgent">
                                        <span class="badge bg-warning">Urgent</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify_critical" name="notify_critical" {{ $checked('notify_critical', true) }}>
                                    <label class="form-check-label" for="notify_critical">
                                        <span class="badge bg-danger">Critical</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Do Not Disturb Settings -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-moon me-2"></i>
                                    Do Not Disturb
                                </h6>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="do_not_disturb" name="do_not_disturb" {{ $checked('do_not_disturb') }}>
                                    <label class="form-check-label" for="do_not_disturb">
                                        Enable Do Not Disturb Mode
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="dnd_start" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="dnd_start" name="dnd_start" value="{{ $prefs['dnd_start'] ?? '22:00' }}">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="dnd_end" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="dnd_end" name="dnd_end" value="{{ $prefs['dnd_end'] ?? '06:00' }}">
                            </div>
                        </div>
                        
                    </form>
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CLIENT_PREFS_KEY = 'notificationClientPrefs';
const serverPreferences = @json($preferences ?? []);

document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('notificationSettingsForm');
    const saveBtn = document.getElementById('saveSettingsBtn');
    const testBtn = document.getElementById('testNotificationBtn');
    const testHumanBtn = document.getElementById('testHumanSoundBtn');
    const volumeSlider = document.getElementById('audio_volume');
    const volumeDisplay = document.getElementById('volumeDisplay');
    const voicePreferenceSelect = document.getElementById('voice_preference');
    const requestPermissionBtn = document.getElementById('requestPermissionBtn');
    const permissionStatus = document.getElementById('notificationPermissionStatus');

    await waitForAppConfig();

    volumeSlider.addEventListener('input', (e) => {
        volumeDisplay.textContent = e.target.value + '%';
    });

    voicePreferenceSelect.addEventListener('change', (e) => {
        applyVoicePreference(e.target.value);
    });

    checkNotificationPermission();
    applyClientPreferences(loadClientPreferences());
    await refreshSettingsFromServer();

    saveBtn.addEventListener('click', async () => {
        await saveSettings();
    });

    testBtn.addEventListener('click', () => {
        if (window.workflowNotificationService) {
            window.workflowNotificationService.testSound('standard');
        }
    });

    testHumanBtn.addEventListener('click', () => {
        if (window.workflowNotificationService) {
            window.workflowNotificationService.playHumanLikeSpeech('standard', volumeSlider.value / 100);
        }
    });

    async function waitForAppConfig() {
        while (!window.appConfig) {
            await new Promise(resolve => setTimeout(resolve, 50));
        }
    }

    function loadClientPreferences() {
        try {
            return JSON.parse(localStorage.getItem(CLIENT_PREFS_KEY) || '{}');
        } catch (error) {
            return {};
        }
    }

    function saveClientPreferences(prefs) {
        localStorage.setItem(CLIENT_PREFS_KEY, JSON.stringify(prefs));
        localStorage.setItem('voicePreference', prefs.voice_preference || 'male');
    }

    function applyVoicePreference(preference) {
        if (window.voiceConfig) {
            window.voiceConfig.setVoicePreference(preference);
        }
    }

    function applyClientPreferences(clientPrefs) {
        const defaults = {
            voice_preference: localStorage.getItem('voicePreference') || 'male',
            use_speech_synthesis: true,
            enhanced_audio: true,
        };
        const prefs = { ...defaults, ...clientPrefs };

        if (voicePreferenceSelect) {
            voicePreferenceSelect.value = prefs.voice_preference;
            applyVoicePreference(prefs.voice_preference);
        }

        ['use_speech_synthesis', 'enhanced_audio'].forEach((key) => {
            const input = document.getElementById(key);
            if (input) {
                input.checked = !!prefs[key];
            }
        });
    }

    function applyServerPreferences(settings) {
        Object.keys(settings).forEach((key) => {
            const input = document.getElementById(key);
            if (!input) {
                return;
            }

            if (input.type === 'checkbox') {
                input.checked = !!settings[key];
            } else if (settings[key] !== null && settings[key] !== undefined) {
                input.value = settings[key];
            }
        });

        if (settings.audio_volume !== undefined) {
            volumeDisplay.textContent = settings.audio_volume + '%';
        }
    }

    async function refreshSettingsFromServer() {
        try {
            const response = await fetch(window.appConfig.route('notification-preferences'), {
                method: 'GET',
                headers: window.appConfig.getApiHeaders(),
                credentials: 'same-origin'
            });

            if (!response.ok) {
                if (response.status === 401) {
                    applyServerPreferences(serverPreferences);
                    return;
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response');
            }

            const data = await response.json();
            if (data.success) {
                applyServerPreferences(data.data);
            }
        } catch (error) {
            console.error('Failed to refresh settings:', error);
            if (Object.keys(serverPreferences).length) {
                applyServerPreferences(serverPreferences);
            }
        }
    }

    function collectSettings() {
        const formData = new FormData(form);
        const settings = {};

        for (const [key, value] of formData.entries()) {
            const input = document.getElementById(key);
            if (!input) {
                continue;
            }

            if (input.type === 'checkbox') {
                settings[key] = input.checked;
            } else if (input.type === 'range' || input.type === 'number') {
                settings[key] = parseInt(value, 10);
            } else {
                settings[key] = value;
            }
        }

        form.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
            if (!formData.has(checkbox.name)) {
                settings[checkbox.name] = false;
            }
        });

        if (!settings.do_not_disturb) {
            settings.dnd_start = null;
            settings.dnd_end = null;
        }

        return settings;
    }

    function collectClientPreferences() {
        return {
            voice_preference: voicePreferenceSelect.value,
            use_speech_synthesis: document.getElementById('use_speech_synthesis').checked,
            enhanced_audio: document.getElementById('enhanced_audio').checked,
        };
    }

    async function saveSettings() {
        try {
            const settings = collectSettings();
            const clientPrefs = collectClientPreferences();

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

            const response = await fetch(window.appConfig.route('notification-preferences'), {
                method: 'POST',
                headers: window.appConfig.getApiHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify(settings)
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.success) {
                const message = data.message
                    || (data.errors ? Object.values(data.errors).flat().join(' ') : null)
                    || 'Failed to save settings';
                showToast('Error', message, 'danger');
                return;
            }

            saveClientPreferences(clientPrefs);
            applyServerPreferences(data.data);
            showToast('Success', 'Notification settings saved successfully', 'success');

            if (window.workflowNotificationService) {
                window.workflowNotificationService.preferences = {
                    ...data.data,
                    ...clientPrefs,
                };
                window.workflowNotificationService.checkInterval = (data.data.check_interval || 30) * 1000;

                if (data.data.audio_enabled) {
                    window.workflowNotificationService.startMonitoring();
                } else {
                    window.workflowNotificationService.stopMonitoring();
                }
            }
        } catch (error) {
            console.error('Failed to save settings:', error);
            showToast('Error', 'An error occurred while saving settings', 'danger');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save Settings';
        }
    }
    
    function showToast(title, message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        toast.style.zIndex = '99999';
        toast.innerHTML = `
            <strong>${title}</strong>
            <div>${message}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }
    
    // Check browser notification permission status
    function checkNotificationPermission() {
        if (!('Notification' in window)) {
            permissionStatus.innerHTML = '<small class="text-danger"><i class="bi bi-x-circle me-1"></i>Browser notifications not supported</small>';
            return;
        }
        
        const permission = Notification.permission;
        
        if (permission === 'granted') {
            permissionStatus.innerHTML = '<small class="text-success"><i class="bi bi-check-circle me-1"></i>Browser notifications enabled</small>';
            requestPermissionBtn.style.display = 'none';
        } else if (permission === 'denied') {
            permissionStatus.innerHTML = '<small class="text-danger"><i class="bi bi-x-circle me-1"></i>Browser notifications blocked. Please enable in browser settings.</small>';
            requestPermissionBtn.style.display = 'none';
        } else {
            permissionStatus.innerHTML = '<small class="text-warning"><i class="bi bi-exclamation-circle me-1"></i>Browser notifications not enabled</small>';
            requestPermissionBtn.style.display = 'inline-block';
        }
    }
    
    // Request browser notification permission
    requestPermissionBtn.addEventListener('click', async () => {
        try {
            if (window.workflowNotificationService) {
                const permission = await window.workflowNotificationService.requestNotificationPermission();
                checkNotificationPermission();
                
                if (permission === 'granted') {
                    showToast('Success', 'Browser notifications have been enabled', 'success');
                } else if (permission === 'denied') {
                    showToast('Permission Denied', 'You have blocked browser notifications. You can enable them in your browser settings.', 'warning');
                }
            }
        } catch (error) {
            console.error('Failed to request notification permission:', error);
            showToast('Error', 'Failed to request notification permission', 'danger');
        }
    });
});
</script>
@endpush

