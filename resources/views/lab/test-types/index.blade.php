@extends('layouts.app')

@section('title', 'Test Types')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Test Types</h1>
            <p class="text-secondary mb-0">Manage laboratory test types and their configurations</p>
        </div>
        <div>
            @can('manage_lab_setup')
            <a href="{{ route('lab.test-types.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> New Test Type
            </a>
            @endcan
        </div>
    </div>
    
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 text-dark">Test Types</h5>
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6">
                            <select id="categoryFilter" class="form-select">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}">{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search test types...">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Test Name</th>
                            <th>Category</th>
                            <th>Result template</th>
                            <th>Specimen</th>
                            <th>TAT (Hours)</th>
                            <th>Cost</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="testTypesTable">
                        @forelse($testTypes as $testType)
                        <tr>
                            <td><code>{{ $testType->test_code }}</code></td>
                            <td>
                                <strong>{{ $testType->test_name }}</strong>
                                @if($testType->subcategory)
                                    <br><small class="text-muted">{{ $testType->subcategory }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $testType->category }}</span>
                            </td>
                            <td>
                                @if($testType->template)
                                    <a href="{{ route('lab.templates.show', $testType->template) }}" class="text-decoration-none">{{ $testType->template->template_code }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $testType->specimen_type ?? '-' }}</td>
                            <td>
                                <small>
                                    Routine: {{ $testType->routine_tat_hours ?? '-' }}h<br>
                                    Urgent: {{ $testType->urgent_tat_hours ?? '-' }}h<br>
                                    STAT: {{ $testType->stat_tat_hours ?? '-' }}h
                                </small>
                            </td>
                            <td>
                                <strong>GHS {{ number_format($testType->cost ?? 0, 2) }}</strong>
                                @if($testType->nhis_covered)
                                    <br><small class="text-primary">NHIS: GHS {{ number_format($testType->nhis_cost ?? 0, 2) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $testType->is_active ? 'success' : 'secondary' }}">
                                    {{ $testType->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                @if($testType->nhis_covered)
                                    <span class="badge bg-primary badge-sm">NHIS</span>
                                @endif
                                @if($testType->requires_doctor_approval)
                                    <span class="badge bg-warning badge-sm">Approval</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    @can('manage_lab_setup')
                                    <a href="{{ route('lab.test-types.show', $testType) }}" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('lab.test-types.edit', $testType) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('lab.test-types.destroy', $testType) }}" method="POST" class="d-inline" 
                                          onsubmit="return confirm('Delete this test type? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="text-secondary">
                                    <i class="bi bi-clipboard-x" style="font-size: 3rem;"></i>
                                    <p class="mt-2">No test types found</p>
                                    <p class="small">Create your first test type to get started</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($testTypes->hasPages())
        <div class="card-footer">
            {{ $testTypes->links() }}
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const table = document.getElementById('testTypesTable');
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value.toLowerCase();
        const rows = table.getElementsByTagName('tr');
        
        for (let row of rows) {
            const text = row.textContent.toLowerCase();
            const categoryMatch = !selectedCategory || text.includes(selectedCategory);
            const searchMatch = !searchTerm || text.includes(searchTerm);
            
            row.style.display = (categoryMatch && searchMatch) ? '' : 'none';
        }
    }
    
    searchInput.addEventListener('input', filterTable);
    categoryFilter.addEventListener('change', filterTable);
});
</script>
@endsection
