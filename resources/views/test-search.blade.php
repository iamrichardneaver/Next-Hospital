@extends('layouts.app')

@section('title', 'Test Search')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Global Search Test</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label for="test-search" class="form-label">Test Search Query:</label>
                        <input type="text" id="test-search" class="form-control" placeholder="Enter search query..." value="test">
                        <button type="button" id="test-search-btn" class="btn btn-primary mt-2">Test Search</button>
                    </div>
                    
                    <div id="search-results-display" class="mt-4">
                        <!-- Results will be displayed here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('test-search-btn').addEventListener('click', function() {
    const query = document.getElementById('test-search').value.trim();
    
    if (!query) {
        alert('Please enter a search query');
        return;
    }
    
    const resultsDiv = document.getElementById('search-results-display');
    resultsDiv.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    axios.get('/api/search', {
        params: { q: query },
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        withCredentials: true
    })
    .then(response => {
        console.log('Search response:', response.data);
        
        if (response.data.success) {
            displayTestResults(response.data.data);
        } else {
            resultsDiv.innerHTML = `<div class="alert alert-danger">Error: ${response.data.message}</div>`;
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        resultsDiv.innerHTML = `<div class="alert alert-danger">Search failed: ${error.message}</div>`;
    });
});

function displayTestResults(data) {
    const resultsDiv = document.getElementById('search-results-display');
    
    let html = `
        <div class="alert alert-success">
            <h6>Search Results for "${data.query}"</h6>
            <p class="mb-0">Total: ${data.total_results} results found</p>
        </div>
    `;
    
    if (data.total_results === 0) {
        html += '<div class="alert alert-info">No results found.</div>';
        resultsDiv.innerHTML = html;
        return;
    }
    
    Object.keys(data.all_results).forEach(category => {
        const items = data.all_results[category];
        if (items.length > 0) {
            html += `
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">${category} (${items.length})</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            ${items.map(item => `
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="bi ${item.icon} text-primary me-3 mt-1"></i>
                                                <div>
                                                    <h6 class="card-title mb-1">${item.title}</h6>
                                                    <p class="card-text text-muted small mb-0">${item.subtitle}</p>
                                                    <small class="text-muted">Type: ${item.type}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
        }
    });
    
    resultsDiv.innerHTML = html;
}
</script>
@endpush
