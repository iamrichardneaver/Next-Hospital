@extends('layouts.app')

@section('title', 'Test Templates')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Test Templates</h1>
            <p class="text-secondary mb-0">Manage laboratory test templates</p>
        </div>
        <div>
            @can('manage_lab_setup')
            <a href="{{ route('lab.templates.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> New Template
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
    
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    
    <div class="alert alert-info border-0 mb-4" role="alert">
        <strong>How to use templates:</strong> Create a template here, add parameters (e.g. ALT, AST for LFT), then assign it to a <a href="{{ route('lab.test-types') }}" class="alert-link">Test Type</a> via <strong>Test Types → Edit</strong> → <strong>Result template</strong>. Lab scientists can then enter results for that test.
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 text-dark">Test Templates</h5>
                </div>
                <div class="col-md-6">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search templates...">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Parameters</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="templatesTable">
                        @forelse($templates as $template)
                        <tr>
                            <td><strong class="text-primary">{{ $template->template_code }}</strong></td>
                            <td>{{ $template->template_name }}</td>
                            <td>
                                @if($template->categoryRelation)
                                    <span class="badge bg-info">{{ $template->categoryRelation->name }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ $template->category }}</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $typeColors = [
                                        'quantitative' => 'primary',
                                        'qualitative' => 'success',
                                        'narrative' => 'warning',
                                        'combined' => 'info'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $typeColors[$template->template_type] ?? 'secondary' }}">
                                    {{ ucfirst($template->template_type) }}
                                </span>
                            </td>
                            <td>{{ $template->parameters->count() }} parameters</td>
                            <td>
                                <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}">
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('lab.templates.show', $template) }}" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('edit_lab_requests')
                                    <a href="{{ route('lab.templates.edit', $template) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    @can('delete_lab_requests')
                                    <form action="{{ route('lab.templates.destroy', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <p class="text-secondary">No templates found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($templates->hasPages())
        <div class="card-footer">
            {{ $templates->links() }}
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('templatesTable');
    
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = table.getElementsByTagName('tr');
        
        for (let row of rows) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        }
    });
});
</script>
@endsection

