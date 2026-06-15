@extends('layouts.app')

@section('title', 'Individual Tests')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">Individual Tests</h1>
            <p class="text-secondary mb-0">Manage individual laboratory tests</p>
        </div>
        <div>
            @can('create_lab_requests')
            <a href="{{ route('lab.tests.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> New Test
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
                    <h5 class="mb-0 text-dark">Tests</h5>
                </div>
                <div class="col-md-6">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search tests...">
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
                            <th>Template</th>
                            <th>Cost</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="testsTable">
                        @forelse($tests as $test)
                        <tr>
                            <td><code>{{ $test->test_code }}</code></td>
                            <td>{{ $test->test_name }}</td>
                            <td>
                                @if($test->category)
                                    <span class="badge bg-info">{{ $test->category->name }}</span>
                                @endif
                            </td>
                            <td>
                                @if($test->template)
                                    <small>{{ $test->template->template_name }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>GHS {{ number_format($test->cost ?? 0, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $test->is_active ? 'success' : 'secondary' }}">
                                    {{ $test->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                @if($test->nhis_covered)
                                    <span class="badge bg-primary badge-sm">NHIS</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    @can('edit_lab_requests')
                                    <a href="{{ route('lab.tests.edit', $test) }}" class="btn btn-sm btn-info">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    @can('delete_lab_requests')
                                    <form action="{{ route('lab.tests.destroy', $test) }}" method="POST" class="d-inline" 
                                          onsubmit="return confirm('Delete this test?');">
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
                                <p class="text-secondary">No tests found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($tests->hasPages())
        <div class="card-footer">
            {{ $tests->links() }}
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('testsTable');
    
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

