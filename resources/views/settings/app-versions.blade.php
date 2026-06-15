@extends('layouts.app')

@section('title', 'Mobile App Version Management')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="mb-4">
        <h1 class="h3 mb-1" style="color: #1e3a5f;">
            <i class="bi bi-phone me-2"></i>Mobile App Version Management
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                <li class="breadcrumb-item active">App Versions</li>
            </ol>
        </nav>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="bi bi-app-indicator fs-1 text-primary mb-2"></i>
                    <div class="fs-4 fw-bold text-dark">{{ $statistics['total_versions'] }}</div>
                    <div class="text-muted small">Total Versions</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle fs-1 text-success mb-2"></i>
                    <div class="fs-4 fw-bold text-dark">{{ $statistics['active_versions'] }}</div>
                    <div class="text-muted small">Active Versions</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-triangle fs-1 text-warning mb-2"></i>
                    <div class="fs-4 fw-bold text-dark">{{ $statistics['force_updates'] }}</div>
                    <div class="text-muted small">Force Updates</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="bi bi-phone fs-1 text-info mb-2"></i>
                    <div class="fs-4 fw-bold text-dark">{{ $statistics['android_versions'] }} / {{ $statistics['ios_versions'] }}</div>
                    <div class="text-muted small">Android / iOS</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add New Version Button --}}
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVersionModal">
            <i class="bi bi-plus-circle"></i> Add New Version
        </button>
    </div>

    {{-- Versions Table --}}
    <div class="card">
        <div class="card-header" style="background-color: #1e3a5f;">
            <h5 class="mb-0 text-white">
                <i class="bi bi-list-ul me-2"></i>App Versions
                <span class="badge bg-light text-dark ms-2">{{ $versions->count() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Platform</th>
                            <th>Version</th>
                            <th>Build</th>
                            <th>Force Update</th>
                            <th>Min Version</th>
                            <th>Release Notes</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($versions as $version)
                        <tr>
                            <td>
                                @php
                                    $platformColors = ['android' => 'success', 'ios' => 'info', 'both' => 'primary'];
                                    $platformIcons = ['android' => 'android2', 'ios' => 'apple', 'both' => 'phone'];
                                @endphp
                                <span class="badge bg-{{ $platformColors[$version->platform] ?? 'secondary' }}">
                                    <i class="bi bi-{{ $platformIcons[$version->platform] ?? 'phone' }} me-1"></i>
                                    {{ ucfirst($version->platform) }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-primary">{{ $version->version_name }}</div>
                                <small class="text-muted">Code: {{ $version->version_code }}</small>
                            </td>
                            <td>
                                <span class="badge bg-secondary">Build {{ $version->build_number }}</span>
                            </td>
                            <td>
                                <form action="{{ route('settings.app-versions.toggle-force', $version) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-{{ $version->is_force_update ? 'warning' : 'outline-secondary' }}">
                                        <i class="bi bi-{{ $version->is_force_update ? 'shield-fill-exclamation' : 'shield' }}"></i>
                                        {{ $version->is_force_update ? 'Forced' : 'Optional' }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                @if($version->min_supported_version)
                                    <span class="badge bg-warning">≥ {{ $version->min_supported_version }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ Str::limit($version->release_notes, 50) ?? 'No notes' }}</small>
                            </td>
                            <td>
                                <form action="{{ route('settings.app-versions.toggle-active', $version) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-{{ $version->is_active ? 'success' : 'secondary' }}">
                                        <i class="bi bi-{{ $version->is_active ? 'check-circle-fill' : 'pause-circle' }}"></i>
                                        {{ $version->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li>
                                            <button class="dropdown-item" onclick="editVersion({{ $version->id }}, {{ json_encode($version) }})">
                                                <i class="bi bi-pencil me-2"></i>Edit Version
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('settings.app-versions.destroy', $version) }}" method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to delete this version?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash me-2"></i>Delete Version
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <p class="mb-0">No app versions configured yet</p>
                                <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#addVersionModal">
                                    <i class="bi bi-plus-circle"></i> Add First Version
                                </button>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add Version Modal --}}
<div class="modal fade" id="addVersionModal" tabindex="-1" aria-labelledby="addVersionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #1e3a5f;">
                <h5 class="modal-title text-white" id="addVersionModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Add New App Version
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('settings.app-versions.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Platform <span class="text-danger">*</span></label>
                            <select name="platform" class="form-select" required>
                                <option value="">Select Platform</option>
                                <option value="android">Android</option>
                                <option value="ios">iOS</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Version Name <span class="text-danger">*</span></label>
                            <input type="text" name="version_name" class="form-control" placeholder="e.g., 1.0.1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Version Code <span class="text-danger">*</span></label>
                            <input type="number" name="version_code" class="form-control" placeholder="e.g., 2" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Build Number <span class="text-danger">*</span></label>
                            <input type="number" name="build_number" class="form-control" placeholder="e.g., 2" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Min Supported Version</label>
                            <input type="number" name="min_supported_version" class="form-control" placeholder="Optional" min="1">
                            <small class="text-muted">Force update for versions below this</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Download URL (Android APK)</label>
                            <input type="url" name="download_url" class="form-control" placeholder="https://example.com/app.apk">
                            <small class="text-muted">Direct APK download link for Android</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Play Store URL</label>
                            <input type="url" name="play_store_url" class="form-control" placeholder="https://play.google.com/store/...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">App Store URL (iOS)</label>
                            <input type="url" name="app_store_url" class="form-control" placeholder="https://apps.apple.com/...">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Release Notes</label>
                            <textarea name="release_notes" class="form-control" rows="4" placeholder="What's new in this version..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_force_update" value="0">
                                <input class="form-check-input" type="checkbox" name="is_force_update" value="1" id="is_force_update">
                                <label class="form-check-label" for="is_force_update">
                                    <strong>Force Update</strong>
                                </label>
                            </div>
                            <small class="text-muted">Block app usage until update is complete</small>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    <strong>Active</strong>
                                </label>
                            </div>
                            <small class="text-muted">Enable this version configuration</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Create Version
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Version Modal --}}
<div class="modal fade" id="editVersionModal" tabindex="-1" aria-labelledby="editVersionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #1e3a5f;">
                <h5 class="modal-title text-white" id="editVersionModalLabel">
                    <i class="bi bi-pencil me-2"></i>Edit App Version
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editVersionForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Platform <span class="text-danger">*</span></label>
                            <select name="platform" id="edit_platform" class="form-select" required>
                                <option value="android">Android</option>
                                <option value="ios">iOS</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Version Name <span class="text-danger">*</span></label>
                            <input type="text" name="version_name" id="edit_version_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Version Code <span class="text-danger">*</span></label>
                            <input type="number" name="version_code" id="edit_version_code" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Build Number <span class="text-danger">*</span></label>
                            <input type="number" name="build_number" id="edit_build_number" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Min Supported Version</label>
                            <input type="number" name="min_supported_version" id="edit_min_supported_version" class="form-control" min="1">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Download URL (Android APK)</label>
                            <input type="url" name="download_url" id="edit_download_url" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Play Store URL</label>
                            <input type="url" name="play_store_url" id="edit_play_store_url" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">App Store URL (iOS)</label>
                            <input type="url" name="app_store_url" id="edit_app_store_url" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Release Notes</label>
                            <textarea name="release_notes" id="edit_release_notes" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_force_update" value="0">
                                <input class="form-check-input" type="checkbox" name="is_force_update" value="1" id="edit_is_force_update">
                                <label class="form-check-label" for="edit_is_force_update">
                                    <strong>Force Update</strong>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    <strong>Active</strong>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Version
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function editVersion(id, version) {
    // Set form action
    document.getElementById('editVersionForm').action = '{{ route("settings.app-versions.update", ":id") }}'.replace(':id', id);
    
    // Populate form fields
    document.getElementById('edit_platform').value = version.platform;
    document.getElementById('edit_version_name').value = version.version_name;
    document.getElementById('edit_version_code').value = version.version_code;
    document.getElementById('edit_build_number').value = version.build_number;
    document.getElementById('edit_min_supported_version').value = version.min_supported_version || '';
    document.getElementById('edit_download_url').value = version.download_url || '';
    document.getElementById('edit_play_store_url').value = version.play_store_url || '';
    document.getElementById('edit_app_store_url').value = version.app_store_url || '';
    document.getElementById('edit_release_notes').value = version.release_notes || '';
    document.getElementById('edit_is_force_update').checked = version.is_force_update;
    document.getElementById('edit_is_active').checked = version.is_active;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('editVersionModal'));
    modal.show();
}
</script>
@endpush

