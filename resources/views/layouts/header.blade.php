<header class="main-header">
    <!-- Sidebar Toggle (Mobile) -->
    <button class="header-icon d-md-none" id="sidebar-toggle">
        <i class="bi bi-list"></i>
    </button>
    
    <!-- Enhanced Search Bar -->
    <div class="header-search d-none d-md-block">
        <div class="position-relative">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input type="text" class="form-control ps-5 pe-4" placeholder="Search patients, appointments, drugs, lab results, insurance..." id="global-search" autocomplete="off">
            <button type="button" class="btn btn-sm btn-outline-secondary position-absolute top-50 end-0 translate-middle-y me-2" id="search-filters-btn" style="display: none;">
                <i class="bi bi-funnel"></i>
            </button>
            
            <!-- Search Results Dropdown -->
            <div class="search-results-dropdown" id="search-results" style="display: none;">
                <div class="search-results-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="search-results-title">
                            <i class="bi bi-search me-2"></i>Search Results
                        </span>
                        <div class="search-actions">
                            <span class="search-results-count badge bg-primary me-2" id="search-count">0</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-search" title="Clear Search">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="search-results-content" id="search-content">
                    <!-- Results will be populated here -->
                </div>
                <div class="search-results-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted" id="search-tips">
                            <i class="bi bi-lightbulb me-1"></i>Try searching by name, ID, phone, or any relevant field
                        </small>
                        <a href="#" id="view-all-results" class="btn btn-sm btn-primary">
                            <i class="bi bi-grid-3x3-gap me-1"></i>View All Results
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Header Actions -->
    <div class="header-actions">
        <!-- Branch Switcher (Super Admin & Admin Only) -->
        @if(auth()->user()->hasRole(['super_admin', 'admin']))
        <div class="dropdown me-2">
            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="branchSwitcher" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-geo-alt me-1"></i>
                @php
                    $currentBranch = auth()->user()->branches()->first();
                @endphp
                <span class="d-none d-lg-inline">
                    {{ $currentBranch ? $currentBranch->name : 'Select Branch' }}
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="branchSwitcher" style="min-width: 250px;">
                <li class="px-3 py-2 border-bottom">
                    <small class="text-muted fw-bold">SWITCH BRANCH</small>
                </li>
                @php
                    $branches = \App\Models\Branch::where('is_active', true)->get();
                @endphp
                @foreach($branches as $branch)
                <li>
                    <form action="{{ route('switch-branch') }}" method="POST" class="mb-0">
                        @csrf
                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                        <button type="submit" class="dropdown-item {{ $currentBranch && $currentBranch->id == $branch->id ? 'active' : '' }}">
                            <i class="bi bi-geo-alt me-2"></i>
                            {{ $branch->name }}
                            @if($currentBranch && $currentBranch->id == $branch->id)
                                <i class="bi bi-check-circle text-success ms-2"></i>
                            @endif
                        </button>
                    </form>
                </li>
                @endforeach
                @if($branches->count() == 0)
                <li>
                    <span class="dropdown-item text-muted">
                        <i class="bi bi-info-circle me-2"></i>
                        No branches available
                    </span>
                </li>
                @endif
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="{{ route('branches.index') }}">
                        <i class="bi bi-gear me-2"></i>
                        Manage Branches
                    </a>
                </li>
            </ul>
        </div>
        @endif
        
        <!-- Notifications -->
        <div class="dropdown" id="notifications-dropdown">
            <div class="header-icon" data-bs-toggle="dropdown" aria-expanded="false" id="notifications-trigger">
                <i class="bi bi-bell"></i>
                <span class="badge" id="notifications-badge">{{ auth()->user()->unreadNotifications->count() }}</span>
            </div>
            
            <div class="dropdown-menu dropdown-menu-end" style="width: 360px; max-height: 450px; overflow-y: auto;">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom sticky-top bg-white">
                    <h6 class="mb-0">Notifications</h6>
                    <div>
                        <small class="text-muted me-2"><span id="notifications-count">{{ auth()->user()->unreadNotifications->count() }}</span> new</small>
                        @if(auth()->user()->unreadNotifications->count() > 0)
                            <button type="button" class="btn btn-sm btn-link p-0" id="mark-all-read-header" title="Mark all as read">
                                <i class="bi bi-check-all"></i>
                            </button>
                        @endif
                    </div>
                </div>
                
                <div id="notifications-list">
                    @forelse(auth()->user()->unreadNotifications->take(8) as $notification)
                        <a href="{{ $notification->data['url'] ?? '#' }}" class="dropdown-item py-3 notification-item" data-id="{{ $notification->id }}" onclick="markNotificationAsRead({{ $notification->id }})">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="notification-icon-small {{ isset($notification->data['priority']) && $notification->data['priority'] === 'high' ? 'bg-danger' : 'bg-primary' }}">
                                        <i class="bi bi-{{ $notification->data['icon'] ?? 'bell' }}"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-1 small fw-semibold">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                    <p class="mb-1 small text-muted">{{ Str::limit($notification->data['message'] ?? 'New notification', 60) }}</p>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="dropdown-item text-center py-4">
                            <i class="bi bi-bell-slash text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mb-0 mt-2">No new notifications</p>
                        </div>
                    @endforelse
                </div>
                
                <div class="dropdown-item text-center border-top sticky-bottom bg-white">
                    <a href="{{ route('notifications.index') }}" class="small text-decoration-none">
                        <i class="bi bi-arrow-right-circle me-1"></i>View all notifications
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <div class="dropdown" id="messages-dropdown">
            <div class="header-icon" data-bs-toggle="dropdown" aria-expanded="false" id="messages-trigger">
                <i class="bi bi-chat-dots"></i>
                <span class="badge" id="messages-badge">0</span>
            </div>
            
            <div class="dropdown-menu dropdown-menu-end" style="width: 360px; max-height: 450px; overflow-y: auto;">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom sticky-top bg-white">
                    <h6 class="mb-0">Messages</h6>
                    <small class="text-muted"><span id="messages-count">0</span> unread</small>
                </div>
                
                <div id="messages-list">
                    <div class="text-center py-4" id="messages-loading">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                
                <div class="dropdown-item text-center border-top sticky-bottom bg-white">
                    <a href="{{ route('messages.index') }}" class="small text-decoration-none">
                        <i class="bi bi-arrow-right-circle me-1"></i>View all messages
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="dropdown">
            <div class="header-icon" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-plus-circle"></i>
            </div>
            
            <div class="dropdown-menu dropdown-menu-end">
                @can('create_patients')
                <a class="dropdown-item" href="{{ route('patients.create') }}">
                    <i class="bi bi-person-plus me-2"></i> New Patient
                </a>
                @endcan
                
                @can('create_appointments')
                <a class="dropdown-item" href="{{ route('appointments.create') }}">
                    <i class="bi bi-calendar-plus me-2"></i> New Appointment
                </a>
                @endcan
                
                @can('create_consultations')
                <a class="dropdown-item" href="{{ route('consultations.create-request') }}">
                    <i class="bi bi-clipboard-plus me-2"></i> New Consultation
                </a>
                @endcan
                
                <div class="dropdown-divider"></div>
                
                @canany(['view_invoices', 'manage_billing', 'create_invoices'])
                <a class="dropdown-item" href="{{ route('billing.create') }}">
                    <i class="bi bi-receipt me-2"></i> New Invoice
                </a>
                @endcanany
            </div>
        </div>
        
        <!-- User Dropdown -->
        <div class="dropdown">
            <div class="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar">
                    @if(auth()->user()->staffProfile && auth()->user()->staffProfile->photo)
                        <img src="{{ asset('storage/' . auth()->user()->staffProfile->photo) }}" alt="User" class="rounded-circle" width="40" height="40">
                    @else
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    @endif
                </div>
                <div class="d-none d-md-block">
                    <div class="fw-bold text-white" style="font-size: 14px;">{{ auth()->user()->name }}</div>
                    <small class="text-light opacity-75">{{ auth()->user()->roles->first()->name ?? 'User' }}</small>
                </div>
                <i class="bi bi-chevron-down ms-2 text-white"></i>
            </div>
            
            <div class="dropdown-menu dropdown-menu-end">
                <div class="px-3 py-2 border-bottom">
                    <div class="fw-bold text-dark">{{ auth()->user()->name }}</div>
                    <small class="text-muted">{{ auth()->user()->email }}</small>
                </div>
                
                <a class="dropdown-item" href="{{ route('profile.show') }}">
                    <i class="bi bi-person me-2"></i> My Profile
                </a>
                
                <a class="dropdown-item" href="{{ route('shop.index') }}" target="_blank">
                    <i class="bi bi-shop me-2"></i> Visit Shop
                    <i class="bi bi-box-arrow-up-right ms-1 small text-muted"></i>
                </a>
                
                @can('view_settings')
                <a class="dropdown-item" href="{{ route('settings.index') }}">
                    <i class="bi bi-gear me-2"></i> Settings
                </a>
                @endcan
                
                <div class="dropdown-divider"></div>
                
                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

@push('styles')
@include('layouts.search-styles')
@endpush

@push('scripts')
<script>
// Enhanced global search functionality with intelligent results
let searchTimeout;
let currentQuery = '';
let currentResults = {};
let searchHistory = [];

document.getElementById('global-search').addEventListener('input', function(e) {
    const query = e.target.value.trim();
    currentQuery = query;
    
    // Clear previous timeout
    clearTimeout(searchTimeout);
    
    // Hide results if query is too short
    if (query.length < 2) {
        hideSearchResults();
        return;
    }
    
    // Show loading state
    showSearchLoading();
    
    // Debounce the search
    searchTimeout = setTimeout(() => {
        if (currentQuery === query) { // Make sure query hasn't changed
            performGlobalSearch(query);
        }
    }, 300);
});

// Handle search input focus
document.getElementById('global-search').addEventListener('focus', function(e) {
    if (currentQuery.length >= 2) {
        showSearchResults();
    }
});

// Enhanced keyboard navigation
document.getElementById('global-search').addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideSearchResults();
        this.blur();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (currentQuery.length >= 2 && Object.keys(currentResults).length > 0) {
            showAllSearchResults(currentResults);
        }
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        // Navigate to first result
        const firstResult = document.querySelector('.search-result-item');
        if (firstResult) {
            firstResult.focus();
        }
    }
});

// Clear search functionality
document.addEventListener('DOMContentLoaded', function() {
    const clearSearchBtn = document.getElementById('clear-search');
    const searchFiltersBtn = document.getElementById('search-filters-btn');
    
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            const searchInput = document.getElementById('global-search');
            searchInput.value = '';
            currentQuery = '';
            currentResults = {};
            hideSearchResults();
            if (searchFiltersBtn) {
                searchFiltersBtn.style.display = 'none';
            }
            searchInput.focus();
        });
    }
    
    // Load search history
    loadSearchHistory();
});

// Handle clicking outside to close search results
document.addEventListener('click', function(e) {
    const searchContainer = document.querySelector('.header-search');
    if (!searchContainer.contains(e.target)) {
        hideSearchResults();
    }
});

// Handle escape key to close search results
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideSearchResults();
        document.getElementById('global-search').blur();
    }
});

function performGlobalSearch(query) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    axios.get('{{ url("/api/search") }}', {
        params: { q: query },
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        withCredentials: true
    })
    .then(response => {
        if (response.data.success) {
            currentResults = response.data.data.all_results;
            displaySearchResults(response.data.data);
            // Add to search history
            addToSearchHistory(query);
        } else {
            showSearchError(response.data.message);
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        if (error.response && error.response.status === 401) {
            showSearchError('Please log in to search.');
        } else {
            showSearchError('Search failed. Please try again.');
        }
    });
}

function displaySearchResults(data) {
    const resultsContainer = document.getElementById('search-content');
    const countElement = document.getElementById('search-count');
    
    // Update count
    countElement.textContent = `${data.total_results} results found`;
    
    // Clear previous results
    resultsContainer.innerHTML = '';
    
    if (data.total_results === 0) {
        resultsContainer.innerHTML = `
            <div class="search-no-results">
                <i class="bi bi-search text-muted"></i>
                <p class="text-muted mb-0">No results found for "${currentQuery}"</p>
            </div>
        `;
        showSearchResults();
        return;
    }
    
    // Display quick results by category
    const quickResults = data.quick_results;
    
    Object.keys(quickResults).forEach(category => {
        const items = quickResults[category];
        if (items.length > 0) {
            const categoryElement = createCategorySection(category, items);
            resultsContainer.appendChild(categoryElement);
        }
    });
    
    // Set up view all results link
    document.getElementById('view-all-results').onclick = function(e) {
        e.preventDefault();
        showAllSearchResults(data.all_results);
    };
    
    showSearchResults();
}

function createCategorySection(category, items) {
    const categoryDiv = document.createElement('div');
    categoryDiv.className = 'search-category';
    
    const categoryHeader = document.createElement('div');
    categoryHeader.className = 'search-category-header';
    categoryHeader.innerHTML = `
        <span class="search-category-title">
            <i class="bi ${getCategoryIcon(category)} me-2"></i>${category}
        </span>
        <span class="search-category-count badge bg-secondary">${items.length}</span>
    `;
    
    const itemsList = document.createElement('div');
    itemsList.className = 'search-category-items';
    
    items.forEach((item, index) => {
        const itemElement = createResultItem(item, index);
        itemsList.appendChild(itemElement);
    });
    
    categoryDiv.appendChild(categoryHeader);
    categoryDiv.appendChild(itemsList);
    
    return categoryDiv;
}

function getCategoryIcon(category) {
    const icons = {
        'Patients': 'bi-person',
        'Appointments': 'bi-calendar-event',
        'Users': 'bi-people',
        'Consultations': 'bi-chat-dots',
        'Visits': 'bi-door-open',
        'Drugs': 'bi-capsule',
        'Store Items': 'bi-box',
        'Lab Requests': 'bi-clipboard-data',
        'Lab Results': 'bi-file-earmark-medical',
        'Lab Templates': 'bi-clipboard-data',
        'Prescriptions': 'bi-prescription',
        'Invoices': 'bi-receipt',
        'Complaints': 'bi-exclamation-triangle',
        'Branches': 'bi-building',
        'Wards': 'bi-hospital',
        'Beds': 'bi-bed',
        'Insurance Providers': 'bi-shield-check',
        'Insurance Policies': 'bi-file-earmark-medical',
        'Insurance Claims': 'bi-receipt',
        'Radiology Requests': 'bi-camera',
        'Radiology Results': 'bi-file-medical',
        'Blood Donations': 'bi-droplet',
        'Blood Inventory': 'bi-bag',
        'Surgery Schedules': 'bi-scissors',
        'Theatres': 'bi-hospital',
        'Walk-ins': 'bi-person-walking'
    };
    return icons[category] || 'bi-search';
}

function createSearchResultItem(item) {
    const itemDiv = document.createElement('a');
    itemDiv.className = 'search-result-item';
    itemDiv.href = item.url;
    
    itemDiv.innerHTML = `
        <div class="search-result-icon">
            <i class="bi ${item.icon}"></i>
        </div>
        <div class="search-result-content">
            <div class="search-result-title">${highlightQuery(item.title, currentQuery)}</div>
            <div class="search-result-subtitle">${item.subtitle}</div>
        </div>
    `;
    
    return itemDiv;
}

function highlightQuery(text, query) {
    if (!query) return text;
    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<strong>$1</strong>');
}

function showSearchLoading() {
    const resultsContainer = document.getElementById('search-content');
    resultsContainer.innerHTML = `
        <div class="search-loading">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span class="ms-2">Searching...</span>
        </div>
    `;
    showSearchResults();
}

function showSearchError(message) {
    const resultsContainer = document.getElementById('search-content');
    resultsContainer.innerHTML = `
        <div class="search-error">
            <i class="bi bi-exclamation-triangle text-warning"></i>
            <p class="text-muted mb-0">${message}</p>
        </div>
    `;
    showSearchResults();
}

function showSearchResults() {
    document.getElementById('search-results').style.display = 'block';
}

function hideSearchResults() {
    document.getElementById('search-results').style.display = 'none';
}

function showAllSearchResults(allResults) {
    // Create a modal or redirect to a dedicated search results page
    // For now, we'll create a simple modal
    const modalHtml = `
        <div class="modal fade" id="searchResultsModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Search Results for "${currentQuery}"</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${generateAllResultsHtml(allResults)}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('searchResultsModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('searchResultsModal'));
    modal.show();
    
    // Hide dropdown
    hideSearchResults();
}

function generateAllResultsHtml(allResults) {
    let html = '';
    
    Object.keys(allResults).forEach(category => {
        const items = allResults[category];
        if (items.length > 0) {
            html += `
                <div class="mb-4">
                    <h6 class="text-primary border-bottom pb-2">${category} (${items.length})</h6>
                    <div class="row">
                        ${items.map(item => `
                            <div class="col-md-6 mb-3">
                                <a href="${item.url}" class="text-decoration-none">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="bi ${item.icon} text-primary me-3 mt-1"></i>
                                                <div>
                                                    <h6 class="card-title mb-1">${highlightQuery(item.title, currentQuery)}</h6>
                                                    <p class="card-text text-muted small mb-0">${item.subtitle}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
    });
    
    return html || '<p class="text-muted">No results found.</p>';
}


// Search history functions
function addToSearchHistory(query) {
    if (query && query.length >= 2) {
        // Remove if already exists
        searchHistory = searchHistory.filter(item => item !== query);
        // Add to beginning
        searchHistory.unshift(query);
        // Keep only last 10 searches
        searchHistory = searchHistory.slice(0, 10);
        // Save to localStorage
        localStorage.setItem('searchHistory', JSON.stringify(searchHistory));
    }
}

function loadSearchHistory() {
    try {
        const saved = localStorage.getItem('searchHistory');
        if (saved) {
            searchHistory = JSON.parse(saved);
        }
    } catch (e) {
        console.warn('Could not load search history:', e);
        searchHistory = [];
    }
}

function getSearchSuggestions() {
    return searchHistory.slice(0, 5);
}

// Enhanced result item creation with keyboard navigation
function createResultItem(item, index) {
    const itemElement = document.createElement('div');
    itemElement.className = 'search-result-item';
    itemElement.tabIndex = 0;
    itemElement.setAttribute('data-index', index);
    
    itemElement.innerHTML = `
        <a href="${item.url}" class="search-result-link">
            <div class="d-flex align-items-start">
                <div class="search-result-icon">
                    <i class="bi ${item.icon}"></i>
                </div>
                <div class="search-result-content">
                    <div class="search-result-title">${highlightQuery(item.title, currentQuery)}</div>
                    <div class="search-result-subtitle">${item.subtitle}</div>
                    <div class="search-result-category">${item.category}</div>
                </div>
            </div>
        </a>
    `;
    
    // Keyboard navigation
    itemElement.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            window.location.href = item.url;
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            const prevItem = document.querySelector(`[data-index="${index - 1}"]`);
            if (prevItem) {
                prevItem.focus();
            } else {
                document.getElementById('global-search').focus();
            }
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            const nextItem = document.querySelector(`[data-index="${index + 1}"]`);
            if (nextItem) {
                nextItem.focus();
            }
        } else if (e.key === 'Escape') {
            document.getElementById('global-search').focus();
            hideSearchResults();
        }
    });
    
    return itemElement;
}

// Debounce utility function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ============================================
// NOTIFICATION AND MESSAGE SYSTEM
// ============================================

// Initialize notification and message system
document.addEventListener('DOMContentLoaded', function() {
    loadLatestNotifications();
    loadLatestMessages();
    
    // Refresh every 30 seconds
    setInterval(loadLatestNotifications, 30000);
    setInterval(loadLatestMessages, 30000);
    
    // Load messages when dropdown is opened
    const messagesDropdown = document.getElementById('messages-trigger');
    if (messagesDropdown) {
        messagesDropdown.addEventListener('click', function() {
            loadLatestMessages();
        });
    }
    
    // Mark all notifications as read
    const markAllReadBtn = document.getElementById('mark-all-read-header');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            markAllNotificationsAsRead();
        });
    }
});

// Load latest notifications
function loadLatestNotifications() {
    const url = window.appConfig ? window.appConfig.route('notifications/latest') : '{{ url("/notifications/latest") }}';
    
    axios.get(url, {
        params: { limit: 8 }
    })
    .then(response => {
        if (response.data.success) {
            updateNotificationBadge(response.data.unread_count);
            // Don't update the list if dropdown is open to avoid disrupting user
            if (!document.getElementById('notifications-dropdown').classList.contains('show')) {
                updateNotificationsList(response.data.data);
            }
        }
    })
    .catch(error => {
        console.error('Error loading notifications:', error);
    });
}

// Update notification badge
function updateNotificationBadge(count) {
    const badge = document.getElementById('notifications-badge');
    const countElement = document.getElementById('notifications-count');
    
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    }
    
    if (countElement) {
        countElement.textContent = count;
    }
}

// Update notifications list
function updateNotificationsList(notifications) {
    const list = document.getElementById('notifications-list');
    if (!list) return;
    
    if (notifications.length === 0) {
        list.innerHTML = `
            <div class="dropdown-item text-center py-4">
                <i class="bi bi-bell-slash text-muted" style="font-size: 2rem;"></i>
                <p class="text-muted mb-0 mt-2">No new notifications</p>
            </div>
        `;
        return;
    }
    
    list.innerHTML = notifications.map(notification => {
        const priorityClass = notification.priority === 'high' ? 'bg-danger' : 'bg-primary';
        return `
            <a href="${notification.url}" class="dropdown-item py-3 notification-item" data-id="${notification.id}" onclick="markNotificationAsRead(${notification.id})">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <div class="notification-icon-small ${priorityClass}">
                            <i class="bi bi-${notification.icon}"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="mb-1 small fw-semibold">${notification.title}</p>
                        <p class="mb-1 small text-muted">${notification.message.substring(0, 60)}${notification.message.length > 60 ? '...' : ''}</p>
                        <small class="text-muted"><i class="bi bi-clock me-1"></i>${notification.created_at}</small>
                    </div>
                </div>
            </a>
        `;
    }).join('');
}

// Mark notification as read
function markNotificationAsRead(notificationId) {
    const url = window.appConfig ? window.appConfig.route(`notifications/${notificationId}/mark-read`) : `{{ url("/notifications") }}/${notificationId}/mark-read`;
    
    axios.post(url, {}, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => {
        if (response.data.success) {
            updateNotificationBadge(response.data.unread_count);
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

// Mark all notifications as read
function markAllNotificationsAsRead() {
    const url = window.appConfig ? window.appConfig.route('notifications/mark-all-read') : '{{ url("/notifications/mark-all-read") }}';
    
    axios.post(url, {}, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => {
        if (response.data.success) {
            updateNotificationBadge(0);
            loadLatestNotifications();
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
    });
}

// Load latest messages
function loadLatestMessages() {
    const loadingElement = document.getElementById('messages-loading');
    if (loadingElement) {
        loadingElement.style.display = 'block';
    }
    
    const url = window.appConfig ? window.appConfig.route('messages/latest') : '{{ url("/messages/latest") }}';
    
    axios.get(url, {
        params: { limit: 8 }
    })
    .then(response => {
        if (response.data.success) {
            updateMessageBadge(response.data.total_unread);
            updateMessagesList(response.data.data);
        }
    })
    .catch(error => {
        console.error('Error loading messages:', error);
        const list = document.getElementById('messages-list');
        if (list) {
            list.innerHTML = `
                <div class="dropdown-item text-center py-4">
                    <i class="bi bi-exclamation-triangle text-warning"></i>
                    <p class="text-muted mb-0 mt-2">Failed to load messages</p>
                </div>
            `;
        }
    })
    .finally(() => {
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    });
}

// Update message badge
function updateMessageBadge(count) {
    const badge = document.getElementById('messages-badge');
    const countElement = document.getElementById('messages-count');
    
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    }
    
    if (countElement) {
        countElement.textContent = count;
    }
}

// Update messages list
function updateMessagesList(conversations) {
    const list = document.getElementById('messages-list');
    if (!list) return;
    
    if (conversations.length === 0) {
        list.innerHTML = `
            <div class="dropdown-item text-center py-4">
                <i class="bi bi-chat-dots text-muted" style="font-size: 2rem;"></i>
                <p class="text-muted mb-0 mt-2">No messages</p>
                <a href="{{ route('messages.index') }}" class="btn btn-sm btn-primary mt-2">Start a Conversation</a>
            </div>
        `;
        return;
    }
    
    list.innerHTML = conversations.map(conversation => {
        const participant = conversation.participants[0] || {};
        const avatarUrl = participant.avatar || '';
        const avatarInitial = participant.name ? participant.name.charAt(0).toUpperCase() : '?';
        const unreadBadge = conversation.unread_count > 0 ? 
            `<span class="badge bg-primary rounded-pill">${conversation.unread_count}</span>` : '';
        
        return `
            <a href="${conversation.url}" class="dropdown-item py-3 message-item">
                <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                        <div class="message-avatar-small">
                            ${avatarUrl ? 
                                `<img src="${avatarUrl}" alt="${participant.name}">` : 
                                `<div class="avatar-text-small">${avatarInitial}</div>`
                            }
                        </div>
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <p class="mb-0 small fw-semibold text-truncate">${conversation.subject}</p>
                            ${unreadBadge}
                        </div>
                        <p class="mb-0 small text-muted text-truncate">${conversation.latest_message.text}</p>
                        <small class="text-muted">${conversation.latest_message.time}</small>
                    </div>
                </div>
            </a>
        `;
    }).join('');
}
</script>

<style>
/* Notification and Message Styles */
.notification-icon-small {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.notification-item {
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: #f8f9fa !important;
}

.message-avatar-small {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
}

.message-avatar-small img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-text-small {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #007bff;
    color: white;
    font-weight: bold;
}

.message-item {
    transition: background-color 0.2s;
}

.message-item:hover {
    background-color: #f8f9fa !important;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 1020;
}

.sticky-bottom {
    position: sticky;
    bottom: 0;
    z-index: 1020;
}

.min-width-0 {
    min-width: 0;
}
</style>
@endpush
