@extends('layouts.app')

@section('title', 'Jitsi Meet Settings')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Jitsi Meet Settings</h1>
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('settings.index') }}" class="text-muted text-hover-primary">Settings</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">Jitsi Meet</li>
                    </ul>
                </div>
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <button class="btn btn-sm fw-bold btn-primary" onclick="testConnection()">
                        <i class="bi bi-wifi"></i> Test Connection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <form action="{{ route('settings.jitsi.update') }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <!-- Basic Settings -->
                    <div class="col-lg-6">
                        <div class="card mb-5 mb-xl-8">
                            <div class="card-header border-0 pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold fs-3 mb-1">Basic Settings</span>
                                </h3>
                            </div>
                            <div class="card-body py-3">
                                <div class="form-check form-switch mb-5">
                                    <input class="form-check-input" type="checkbox" name="enabled" value="1" 
                                           {{ $settings->enabled ? 'checked' : '' }}>
                                    <label class="form-check-label">Enable Jitsi Meet Integration</label>
                                </div>

                                <div class="mb-5">
                                    <label class="form-label required">Server URL</label>
                                    <input type="url" name="server_url" class="form-control @error('server_url') is-invalid @enderror" 
                                           value="{{ old('server_url', $settings->server_url) }}" required>
                                    @error('server_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Default: https://meet.jit.si (public server)</div>
                                </div>

                                <div class="mb-5">
                                    <label class="form-label">App ID</label>
                                    <input type="text" name="app_id" class="form-control @error('app_id') is-invalid @enderror" 
                                           value="{{ old('app_id', $settings->app_id) }}">
                                    @error('app_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Required for JWT authentication</div>
                                </div>

                                <div class="mb-5">
                                    <label class="form-label">App Secret</label>
                                    <input type="password" name="app_secret" class="form-control @error('app_secret') is-invalid @enderror" 
                                           value="{{ old('app_secret', $settings->app_secret) }}">
                                    @error('app_secret')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Required for JWT authentication</div>
                                </div>

                                <div class="mb-5">
                                    <label class="form-label">JWT Secret</label>
                                    <input type="password" name="jwt_secret" class="form-control @error('jwt_secret') is-invalid @enderror" 
                                           value="{{ old('jwt_secret', $settings->jwt_secret) }}">
                                    @error('jwt_secret')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Secret key for JWT token generation</div>
                                </div>

                                <div class="mb-5">
                                    <label class="form-label required">JWT Algorithm</label>
                                    <select name="jwt_algorithm" class="form-select @error('jwt_algorithm') is-invalid @enderror" required>
                                        <option value="HS256" {{ old('jwt_algorithm', $settings->jwt_algorithm) == 'HS256' ? 'selected' : '' }}>HS256</option>
                                        <option value="HS384" {{ old('jwt_algorithm', $settings->jwt_algorithm) == 'HS384' ? 'selected' : '' }}>HS384</option>
                                        <option value="HS512" {{ old('jwt_algorithm', $settings->jwt_algorithm) == 'HS512' ? 'selected' : '' }}>HS512</option>
                                        <option value="RS256" {{ old('jwt_algorithm', $settings->jwt_algorithm) == 'RS256' ? 'selected' : '' }}>RS256</option>
                                        <option value="RS384" {{ old('jwt_algorithm', $settings->jwt_algorithm) == 'RS384' ? 'selected' : '' }}>RS384</option>
                                        <option value="RS512" {{ old('jwt_algorithm', $settings->jwt_algorithm) == 'RS512' ? 'selected' : '' }}>RS512</option>
                                    </select>
                                    @error('jwt_algorithm')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-5">
                                    <label class="form-label required">Meeting Duration (minutes)</label>
                                    <input type="number" name="meeting_duration_minutes" class="form-control @error('meeting_duration_minutes') is-invalid @enderror" 
                                           value="{{ old('meeting_duration_minutes', $settings->meeting_duration_minutes) }}" 
                                           min="15" max="480" required>
                                    @error('meeting_duration_minutes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-5">
                                    <label class="form-label required">Max Participants</label>
                                    <input type="number" name="max_participants" class="form-control @error('max_participants') is-invalid @enderror" 
                                           value="{{ old('max_participants', $settings->max_participants) }}" 
                                           min="2" max="1000" required>
                                    @error('max_participants')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-5">
                                    <label class="form-label required">Default Language</label>
                                    <select name="default_language" class="form-select @error('default_language') is-invalid @enderror" required>
                                        <option value="en" {{ old('default_language', $settings->default_language) == 'en' ? 'selected' : '' }}>English</option>
                                        <option value="es" {{ old('default_language', $settings->default_language) == 'es' ? 'selected' : '' }}>Spanish</option>
                                        <option value="fr" {{ old('default_language', $settings->default_language) == 'fr' ? 'selected' : '' }}>French</option>
                                        <option value="de" {{ old('default_language', $settings->default_language) == 'de' ? 'selected' : '' }}>German</option>
                                        <option value="it" {{ old('default_language', $settings->default_language) == 'it' ? 'selected' : '' }}>Italian</option>
                                        <option value="pt" {{ old('default_language', $settings->default_language) == 'pt' ? 'selected' : '' }}>Portuguese</option>
                                        <option value="ru" {{ old('default_language', $settings->default_language) == 'ru' ? 'selected' : '' }}>Russian</option>
                                        <option value="zh" {{ old('default_language', $settings->default_language) == 'zh' ? 'selected' : '' }}>Chinese</option>
                                        <option value="ja" {{ old('default_language', $settings->default_language) == 'ja' ? 'selected' : '' }}>Japanese</option>
                                        <option value="ko" {{ old('default_language', $settings->default_language) == 'ko' ? 'selected' : '' }}>Korean</option>
                                    </select>
                                    @error('default_language')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-5">
                                    <label class="form-label required">Default Timezone</label>
                                    <select name="default_timezone" class="form-select @error('default_timezone') is-invalid @enderror" required>
                                        <option value="UTC" {{ old('default_timezone', $settings->default_timezone) == 'UTC' ? 'selected' : '' }}>UTC</option>
                                        <option value="Africa/Accra" {{ old('default_timezone', $settings->default_timezone) == 'Africa/Accra' ? 'selected' : '' }}>Africa/Accra (GMT+0)</option>
                                        <option value="America/New_York" {{ old('default_timezone', $settings->default_timezone) == 'America/New_York' ? 'selected' : '' }}>America/New_York (GMT-5)</option>
                                        <option value="Europe/London" {{ old('default_timezone', $settings->default_timezone) == 'Europe/London' ? 'selected' : '' }}>Europe/London (GMT+0)</option>
                                        <option value="Asia/Tokyo" {{ old('default_timezone', $settings->default_timezone) == 'Asia/Tokyo' ? 'selected' : '' }}>Asia/Tokyo (GMT+9)</option>
                                    </select>
                                    @error('default_timezone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feature Settings -->
                    <div class="col-lg-6">
                        <div class="card mb-5 mb-xl-8">
                            <div class="card-header border-0 pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold fs-3 mb-1">Feature Settings</span>
                                </h3>
                            </div>
                            <div class="card-body py-3">
                                <div class="form-check form-switch mb-5">
                                    <input class="form-check-input" type="checkbox" name="recording_enabled" value="1" 
                                           {{ $settings->recording_enabled ? 'checked' : '' }}>
                                    <label class="form-check-label">Enable Recording</label>
                                </div>

                                <div class="form-check form-switch mb-5">
                                    <input class="form-check-input" type="checkbox" name="chat_enabled" value="1" 
                                           {{ $settings->chat_enabled ? 'checked' : '' }}>
                                    <label class="form-check-label">Enable Chat</label>
                                </div>

                                <div class="form-check form-switch mb-5">
                                    <input class="form-check-input" type="checkbox" name="screen_sharing_enabled" value="1" 
                                           {{ $settings->screen_sharing_enabled ? 'checked' : '' }}>
                                    <label class="form-check-label">Enable Screen Sharing</label>
                                </div>

                                <div class="form-check form-switch mb-5">
                                    <input class="form-check-input" type="checkbox" name="file_sharing_enabled" value="1" 
                                           {{ $settings->file_sharing_enabled ? 'checked' : '' }}>
                                    <label class="form-check-label">Enable File Sharing</label>
                                </div>

                                <div class="form-check form-switch mb-5">
                                    <input class="form-check-input" type="checkbox" name="live_streaming_enabled" value="1" 
                                           {{ $settings->live_streaming_enabled ? 'checked' : '' }}>
                                    <label class="form-check-label">Enable Live Streaming</label>
                                </div>

                                <div class="form-check form-switch mb-5">
                                    <input class="form-check-input" type="checkbox" name="transcription_enabled" value="1" 
                                           {{ $settings->transcription_enabled ? 'checked' : '' }}>
                                    <label class="form-check-label">Enable Transcription</label>
                                </div>

                                <div class="form-check form-switch mb-5">
                                    <input class="form-check-input" type="checkbox" name="waiting_room_enabled" value="1" 
                                           {{ $settings->waiting_room_enabled ? 'checked' : '' }}>
                                    <label class="form-check-label">Enable Waiting Room</label>
                                </div>

                                <div class="form-check form-switch mb-5">
                                    <input class="form-check-input" type="checkbox" name="mute_on_entry" value="1" 
                                           {{ $settings->mute_on_entry ? 'checked' : '' }}>
                                    <label class="form-check-label">Mute on Entry</label>
                                </div>

                                <div class="form-check form-switch mb-5">
                                    <input class="form-check-input" type="checkbox" name="require_display_name" value="1" 
                                           {{ $settings->require_display_name ? 'checked' : '' }}>
                                    <label class="form-check-label">Require Display Name</label>
                                </div>

                                <div class="form-check form-switch mb-5">
                                    <input class="form-check-input" type="checkbox" name="require_password" value="1" 
                                           {{ $settings->require_password ? 'checked' : '' }}>
                                    <label class="form-check-label">Require Meeting Password</label>
                                </div>

                                <div class="form-check form-switch mb-5">
                                    <input class="form-check-input" type="checkbox" name="enable_knocking" value="1" 
                                           {{ $settings->enable_knocking ? 'checked' : '' }}>
                                    <label class="form-check-label">Enable Knocking</label>
                                </div>

                                <div class="form-check form-switch mb-5">
                                    <input class="form-check-input" type="checkbox" name="enable_lobby" value="1" 
                                           {{ $settings->enable_lobby ? 'checked' : '' }}>
                                    <label class="form-check-label">Enable Lobby</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="card">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-end gap-3">
                            <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Save Settings
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function testConnection() {
    const button = event.target;
    const originalText = button.innerHTML;
    
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Testing...';
    button.disabled = true;
    
    fetch('{{ route("settings.jitsi.test") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to test connection', 'error');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="bi bi-${type === 'success' ? 'check-circle-fill' : type === 'error' ? 'x-circle-fill' : 'info-circle-fill'} fs-4 me-3"></i>
            <span>${message}</span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>
@endsection
