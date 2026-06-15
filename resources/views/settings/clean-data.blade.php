@extends('layouts.app')

@section('title', 'Data Cleanup - System Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #dc3545;">Data Cleanup</h1>
            <p class="text-secondary mb-0">Selectively clear operational data — reference &amp; config tables are always preserved</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" id="refreshStatsBtn">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Counts
            </button>
            <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Settings
            </a>
        </div>
    </div>
    
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    
    <div class="alert alert-danger border-danger mb-4">
        <div class="d-flex align-items-start">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3 mt-1"></i>
            <div>
                <h5 class="alert-heading mb-2">Critical Warning</h5>
                <p class="mb-2">This feature permanently deletes <strong>operational data</strong> from selected categories. It:</p>
                <ul class="mb-2">
                    <li><strong>Cannot be undone</strong> — deleted records are permanently lost</li>
                    <li><strong>Never touches</strong> users, roles, permissions, branches, pricing, lab catalogs, drug formulary, insurance providers, or settings</li>
                    <li><strong>Requires typed confirmation</strong> before any deletion runs</li>
                </ul>
                <p class="mb-0"><strong>Recommendation:</strong> Create a full database backup before proceeding.</p>
            </div>
        </div>
    </div>

    <div class="alert alert-success mb-4">
        <div class="d-flex align-items-start">
            <i class="bi bi-shield-check fs-4 me-3 mt-1"></i>
            <div>
                <h6 class="mb-2">Protected Reference Data ({{ count($protectedTables) }} tables)</h6>
                <p class="mb-2 small">The following are <strong>never</strong> deleted regardless of selection:</p>
                <p class="mb-0 small text-muted">
                    Users &amp; admins, roles &amp; permissions, branches, service pricing, appointment fees,
                    lab test types/templates, drug formulary, insurance providers &amp; coverage policies,
                    expense categories, imaging modalities, settings &amp; branding, migrations metadata, and more.
                </p>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>System Overview</h5>
        </div>
        <div class="card-body">
            <div class="row" id="systemStatsRow">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-1" id="statModules">{{ $systemStats['total_modules'] }}</h3>
                            <small>Cleanable Categories</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-1" id="statTables">{{ number_format($systemStats['total_tables']) }}</h3>
                            <small>Operational Tables</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-1" id="statRecords">{{ number_format($systemStats['total_records']) }}</h3>
                            <small>Cleanable Records</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-1" id="statProtected">{{ $systemStats['protected_tables'] ?? count($protectedTables) }}</h3>
                            <small>Protected Tables</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <form action="{{ route('settings.process-clean-data') }}" method="POST" id="cleanDataForm">
        @csrf
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Select Categories to Clean</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllBtn">
                        <i class="bi bi-check-all me-1"></i> Select All
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="selectNoneBtn">
                        <i class="bi bi-x-circle me-1"></i> Select None
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="modulesGrid">
                    @foreach($cleanableModules as $key => $module)
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card module-card border-{{ $module['color'] }}" data-module="{{ $key }}">
                            <div class="card-body">
                                <div class="form-check">
                                    <input class="form-check-input module-checkbox" type="checkbox" 
                                           name="modules[]" value="{{ $key }}" id="module_{{ $key }}"
                                           data-module-key="{{ $key }}"
                                           data-tables="{{ count($module['cleanable_tables'] ?? $module['tables']) }}"
                                           data-records="{{ $module['estimated_records'] }}">
                                    <label class="form-check-label w-100" for="module_{{ $key }}">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0 me-3">
                                                <i class="bi {{ $module['icon'] }} fs-4 text-{{ $module['color'] }}"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 text-dark">{{ $module['name'] }}</h6>
                                                <p class="text-muted small mb-2">{{ $module['description'] }}</p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="bi bi-table me-1"></i><span class="module-table-count">{{ count($module['cleanable_tables'] ?? $module['tables']) }}</span> tables
                                                    </small>
                                                    <small class="text-{{ $module['color'] }} fw-bold">
                                                        <i class="bi bi-database me-1"></i><span class="module-record-count">{{ number_format($module['estimated_records']) }}</span> records
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <div class="mt-4" id="selectionSummary" style="display: none;">
                    <div class="alert alert-info">
                        <h6 class="mb-2"><i class="bi bi-info-circle me-2"></i>Selection Summary</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Selected Categories:</strong> <span id="selectedModulesCount">0</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Tables to Clean:</strong> <span id="selectedTablesCount">0</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Records to Remove:</strong> <span id="selectedRecordsCount">0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3" id="previewSection" style="display: none;">
                    <div class="card border-warning">
                        <div class="card-header bg-warning bg-opacity-10">
                            <h6 class="mb-0"><i class="bi bi-eye me-2"></i>Deletion Preview</h6>
                        </div>
                        <div class="card-body">
                            <div id="previewLoading" class="text-muted small" style="display: none;">
                                <span class="spinner-border spinner-border-sm me-2"></span>Calculating preview...
                            </div>
                            <div id="previewContent"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4" id="confirmationSection" style="display: none;">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0 text-white"><i class="bi bi-exclamation-triangle me-2"></i>Final Confirmation</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-danger">
                    <h6 class="mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Final Warning</h6>
                    <p class="mb-0">You are about to permanently delete operational data from the selected categories. Reference tables will remain intact.</p>
                </div>
                
                <div class="mb-3">
                    <label for="confirmationText" class="form-label">
                        <strong>Type "{{ $confirmationPhrase }}" to confirm:</strong>
                    </label>
                    <input type="text" class="form-control" name="confirmation_text" id="confirmationText" 
                           placeholder="Type the confirmation text exactly as shown" required autocomplete="off">
                    <small class="text-muted">Safety measure to prevent accidental deletion.</small>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary" id="cancelBtn">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmCleanBtn" disabled>
                        <i class="bi bi-trash3 me-1"></i> Clean Selected Data
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmationModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirm Data Cleanup
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h6 class="mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Are you absolutely sure?</h6>
                    <p class="mb-0">This will permanently delete operational records. Protected reference data (users, pricing, catalogs, settings) will <strong>not</strong> be touched.</p>
                </div>
                
                <div id="modalSummary"></div>
                
                <div class="mt-3">
                    <label for="modalConfirmationText" class="form-label">
                        <strong>Type "{{ $confirmationPhrase }}" to proceed:</strong>
                    </label>
                    <input type="text" class="form-control" id="modalConfirmationText" 
                           placeholder="Type the confirmation text exactly as shown" autocomplete="off">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="modalConfirmBtn" disabled>
                    <i class="bi bi-trash3 me-1"></i> Yes, Delete Selected Data
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const CONFIRMATION_PHRASE = @json($confirmationPhrase);
    const STATS_URL = @json(route('settings.clean-data.stats'));
    const PREVIEW_URL = @json(route('settings.clean-data.preview'));
    const CSRF_TOKEN = @json(csrf_token());

    const checkboxes = document.querySelectorAll('.module-checkbox');
    const selectAllBtn = document.getElementById('selectAllBtn');
    const selectNoneBtn = document.getElementById('selectNoneBtn');
    const confirmationSection = document.getElementById('confirmationSection');
    const confirmationText = document.getElementById('confirmationText');
    const confirmCleanBtn = document.getElementById('confirmCleanBtn');
    const selectionSummary = document.getElementById('selectionSummary');
    const previewSection = document.getElementById('previewSection');
    const previewContent = document.getElementById('previewContent');
    const previewLoading = document.getElementById('previewLoading');
    const form = document.getElementById('cleanDataForm');

    let latestPreview = null;
    let previewTimer = null;

    checkboxes.forEach(checkbox => checkbox.addEventListener('change', updateSelection));
    
    selectAllBtn.addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = true);
        updateSelection();
    });
    
    selectNoneBtn.addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = false);
        updateSelection();
    });

    document.getElementById('refreshStatsBtn').addEventListener('click', refreshStats);

    confirmationText.addEventListener('input', function() {
        const isValid = this.value === CONFIRMATION_PHRASE;
        confirmCleanBtn.disabled = !isValid;
        this.classList.toggle('is-valid', isValid);
        this.classList.toggle('is-invalid', !isValid && this.value.length > 0);
    });
    
    confirmCleanBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirmationText.value !== CONFIRMATION_PHRASE) {
            alert('Please type the confirmation text exactly as shown.');
            return;
        }
        showConfirmationModal();
    });

    document.getElementById('modalConfirmationText').addEventListener('input', function() {
        const isValid = this.value === CONFIRMATION_PHRASE;
        document.getElementById('modalConfirmBtn').disabled = !isValid;
        this.classList.toggle('is-valid', isValid);
        this.classList.toggle('is-invalid', !isValid && this.value.length > 0);
    });

    document.getElementById('modalConfirmBtn').addEventListener('click', function() {
        if (document.getElementById('modalConfirmationText').value === CONFIRMATION_PHRASE) {
            bootstrap.Modal.getInstance(document.getElementById('confirmationModal')).hide();
            form.submit();
        }
    });
    
    document.getElementById('cancelBtn').addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = false);
        updateSelection();
    });

    function getSelectedModuleKeys() {
        return Array.from(document.querySelectorAll('.module-checkbox:checked')).map(cb => cb.value);
    }

    function updateSelection() {
        const selectedCheckboxes = document.querySelectorAll('.module-checkbox:checked');
        const selectedCount = selectedCheckboxes.length;
        
        if (selectedCount > 0) {
            confirmationSection.style.display = 'block';
            selectionSummary.style.display = 'block';
            previewSection.style.display = 'block';
            
            let totalTables = 0;
            let totalRecords = 0;
            
            selectedCheckboxes.forEach(checkbox => {
                totalTables += parseInt(checkbox.dataset.tables, 10);
                totalRecords += parseInt(checkbox.dataset.records, 10);
            });
            
            document.getElementById('selectedModulesCount').textContent = selectedCount;
            document.getElementById('selectedTablesCount').textContent = totalTables;
            document.getElementById('selectedRecordsCount').textContent = totalRecords.toLocaleString();
            
            confirmationText.value = '';
            confirmationText.classList.remove('is-valid', 'is-invalid');
            confirmCleanBtn.disabled = true;

            clearTimeout(previewTimer);
            previewTimer = setTimeout(loadPreview, 300);
        } else {
            confirmationSection.style.display = 'none';
            selectionSummary.style.display = 'none';
            previewSection.style.display = 'none';
            previewContent.innerHTML = '';
            latestPreview = null;
        }
    }

    async function loadPreview() {
        const modules = getSelectedModuleKeys();
        if (modules.length === 0) return;

        previewLoading.style.display = 'block';
        previewContent.innerHTML = '';

        try {
            const response = await fetch(PREVIEW_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ modules }),
            });

            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Preview failed');

            latestPreview = data.preview;
            renderPreview(data.preview);
        } catch (error) {
            previewContent.innerHTML = `<div class="text-danger small">${error.message}</div>`;
        } finally {
            previewLoading.style.display = 'none';
        }
    }

    function renderPreview(preview) {
        let html = `<p class="mb-2"><strong>Will remove ${preview.total_records.toLocaleString()} records</strong> from ${Object.keys(preview.modules).length} categories:</p><ul class="list-unstyled mb-0">`;

        Object.values(preview.modules).forEach(module => {
            if (module.total_records === 0) return;
            html += `<li class="mb-2"><i class="bi bi-check-circle-fill text-warning me-2"></i>`;
            html += `<strong>${module.name}</strong> — ${module.total_records.toLocaleString()} records<ul class="small text-muted ms-4 mt-1">`;
            Object.entries(module.tables).forEach(([table, count]) => {
                if (count > 0) {
                    html += `<li>${table}: ${count.toLocaleString()}</li>`;
                }
            });
            html += '</ul></li>';
        });

        html += '</ul>';

        if (preview.protected_skipped && preview.protected_skipped.length > 0) {
            html += `<p class="small text-success mt-2 mb-0"><i class="bi bi-shield-check me-1"></i>Protected tables in selection were automatically excluded.</p>`;
        }

        previewContent.innerHTML = html;
    }

    function showConfirmationModal() {
        const selectedCheckboxes = document.querySelectorAll('.module-checkbox:checked');
        let modalSummary = '<h6>Selected Categories:</h6><ul class="list-unstyled">';

        if (latestPreview) {
            Object.entries(latestPreview.modules).forEach(([key, module]) => {
                modalSummary += `<li><i class="bi bi-check-circle-fill text-success me-2"></i>`;
                modalSummary += `<strong>${module.name}</strong> — ${module.total_records.toLocaleString()} records</li>`;
            });
            modalSummary += `</ul><p class="mt-2 mb-0"><strong>Total:</strong> ${latestPreview.total_records.toLocaleString()} records will be permanently deleted.</p>`;
        } else {
            selectedCheckboxes.forEach(checkbox => {
                const moduleCard = checkbox.closest('.module-card');
                const moduleName = moduleCard.querySelector('h6').textContent;
                const recordCount = parseInt(checkbox.dataset.records, 10).toLocaleString();
                modalSummary += `<li><i class="bi bi-check-circle-fill text-success me-2"></i>`;
                modalSummary += `<strong>${moduleName}</strong> (${recordCount} records)</li>`;
            });
            modalSummary += '</ul>';
        }
        
        document.getElementById('modalSummary').innerHTML = modalSummary;
        document.getElementById('modalConfirmationText').value = '';
        document.getElementById('modalConfirmBtn').disabled = true;
        
        new bootstrap.Modal(document.getElementById('confirmationModal')).show();
    }

    async function refreshStats() {
        const btn = document.getElementById('refreshStatsBtn');
        btn.disabled = true;

        try {
            const response = await fetch(STATS_URL, { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Failed to refresh');

            document.getElementById('statModules').textContent = data.system_stats.total_modules;
            document.getElementById('statTables').textContent = Number(data.system_stats.total_tables).toLocaleString();
            document.getElementById('statRecords').textContent = Number(data.system_stats.total_records).toLocaleString();
            document.getElementById('statProtected').textContent = data.protected_tables_count;

            Object.entries(data.modules).forEach(([key, module]) => {
                const checkbox = document.getElementById('module_' + key);
                if (!checkbox) return;
                const tableCount = (module.cleanable_tables || module.tables || []).length;
                checkbox.dataset.tables = tableCount;
                checkbox.dataset.records = module.estimated_records;

                const card = checkbox.closest('.module-card');
                if (card) {
                    const tableEl = card.querySelector('.module-table-count');
                    const recordEl = card.querySelector('.module-record-count');
                    if (tableEl) tableEl.textContent = tableCount;
                    if (recordEl) recordEl.textContent = Number(module.estimated_records).toLocaleString();
                }
            });

            updateSelection();
        } catch (error) {
            alert('Could not refresh counts: ' + error.message);
        } finally {
            btn.disabled = false;
        }
    }
});
</script>
@endpush
