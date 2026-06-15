@extends('layouts.app')

@section('title', 'Messages')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="bi bi-chat-dots text-primary me-2"></i>
                Messages
            </h2>
            <p class="text-muted mb-0">Internal staff messaging</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                <i class="bi bi-plus-circle me-2"></i>New Message
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Conversations List -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Conversations</h5>
                </div>
                <div class="card-body p-0">
                    @forelse($conversations as $conversation)
                        @php
                            $otherParticipants = $conversation->participants->where('id', '!=', auth()->id());
                            $unreadCount = $conversation->unreadCountForUser(auth()->id());
                            $latestMessage = $conversation->latestMessage;
                        @endphp
                        <a href="{{ route('messages.show', $conversation->id) }}" class="conversation-item {{ request()->route('id') == $conversation->id ? 'active' : '' }}">
                            <div class="d-flex">
                                <div class="conversation-avatar me-3">
                                    @if($otherParticipants->count() === 1)
                                        @php
                                            $participant = $otherParticipants->first();
                                        @endphp
                                        @if($participant->staffProfile && $participant->staffProfile->photo)
                                            <img src="{{ asset('storage/' . $participant->staffProfile->photo) }}" alt="{{ $participant->name }}">
                                        @else
                                            <div class="avatar-text">{{ strtoupper(substr($participant->name, 0, 1)) }}</div>
                                        @endif
                                    @else
                                        <div class="avatar-text"><i class="bi bi-people"></i></div>
                                    @endif
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="mb-0 text-truncate">
                                            {{ $conversation->subject ?? $otherParticipants->pluck('name')->join(', ') }}
                                        </h6>
                                        @if($unreadCount > 0)
                                            <span class="badge bg-primary">{{ $unreadCount }}</span>
                                        @endif
                                    </div>
                                    @if($latestMessage)
                                        <p class="mb-0 text-muted small text-truncate">
                                            {{ $latestMessage->sender_id === auth()->id() ? 'You: ' : '' }}
                                            {{ Str::limit($latestMessage->message, 50) }}
                                        </p>
                                        <small class="text-muted">{{ $latestMessage->created_at->diffForHumans() }}</small>
                                    @else
                                        <p class="mb-0 text-muted small">No messages yet</p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="text-center py-5">
                            <i class="bi bi-chat-dots text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3 mb-0">No conversations yet</p>
                            <button type="button" class="btn btn-sm btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                                Start a Conversation
                            </button>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Message Content Area -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-chat-quote text-muted" style="font-size: 4rem;"></i>
                    <p class="text-muted mt-3 mb-0">Select a conversation to view messages</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Message Modal -->
<div class="modal fade" id="newMessageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="new-message-form">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="recipients" class="form-label">To:</label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="recipients-search" placeholder="Search users..." autocomplete="off">
                            <div class="dropdown-menu w-100" id="recipients-dropdown" style="display: none; max-height: 200px; overflow-y: auto;">
                                <!-- Users will be populated here -->
                            </div>
                            <div class="selected-recipients mt-2" id="selected-recipients">
                                <!-- Selected users will be displayed here -->
                            </div>
                        </div>
                        <small class="text-muted">Type to search and select multiple recipients</small>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject (Optional):</label>
                        <input type="text" class="form-control" id="subject" name="subject" placeholder="Enter subject">
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message:</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required placeholder="Type your message..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-2"></i>Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.conversation-item {
    display: block;
    padding: 1rem;
    border-bottom: 1px solid #e0e0e0;
    color: inherit;
    text-decoration: none;
    transition: background-color 0.2s;
}

.conversation-item:hover {
    background-color: #f5f5f5;
}

.conversation-item.active {
    background-color: #007bff;
    color: white;
}

.conversation-item.active .text-muted {
    color: rgba(255, 255, 255, 0.7) !important;
}

.conversation-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.conversation-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-text {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #007bff;
    color: white;
    font-weight: bold;
}

.min-width-0 {
    min-width: 0;
}

/* Multi-select dropdown styles */
#recipients-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1050;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.user-option {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s;
}

.user-option:hover {
    background-color: #f8f9fa;
}

.user-option:last-child {
    border-bottom: none;
}

.user-option.selected {
    background-color: #e3f2fd;
    color: #1976d2;
}

.selected-recipient {
    display: inline-flex;
    align-items: center;
    background-color: #007bff;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    margin: 0.125rem;
    font-size: 0.875rem;
}

.selected-recipient .remove-btn {
    background: none;
    border: none;
    color: white;
    margin-left: 0.5rem;
    cursor: pointer;
    padding: 0;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.selected-recipient .remove-btn:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.no-users-found {
    padding: 1rem;
    text-align: center;
    color: #6c757d;
    font-style: italic;
}
</style>
@endpush

@push('scripts')
<script>
// Multi-select dropdown functionality
let selectedUsers = [];
let allUsers = [];
let searchTimeout;

// Load users for new message
const newMessageModal = document.getElementById('newMessageModal');
newMessageModal.addEventListener('show.bs.modal', function() {
    loadUsers();
    resetForm();
});

// Reset form when modal is closed
newMessageModal.addEventListener('hidden.bs.modal', function() {
    resetForm();
});

function resetForm() {
    selectedUsers = [];
    document.getElementById('recipients-search').value = '';
    document.getElementById('subject').value = '';
    document.getElementById('message').value = '';
    document.getElementById('recipients-dropdown').style.display = 'none';
    updateSelectedRecipients();
}

function loadUsers() {
    axios.get('/messages/users')
        .then(response => {
            if (response.data.success) {
                allUsers = response.data.data;
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
        });
}

// Search functionality
document.getElementById('recipients-search').addEventListener('input', function(e) {
    const query = e.target.value.trim();
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (query.length >= 1) {
            searchUsers(query);
        } else {
            hideDropdown();
        }
    }, 300);
});

// Focus events
document.getElementById('recipients-search').addEventListener('focus', function() {
    if (this.value.trim().length >= 1) {
        searchUsers(this.value.trim());
    }
});

// Click outside to close dropdown
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('recipients-dropdown');
    const searchInput = document.getElementById('recipients-search');
    
    if (!dropdown.contains(e.target) && !searchInput.contains(e.target)) {
        hideDropdown();
    }
});

function searchUsers(query) {
    const filteredUsers = allUsers.filter(user => 
        user.name.toLowerCase().includes(query.toLowerCase()) ||
        user.email.toLowerCase().includes(query.toLowerCase()) ||
        user.role.toLowerCase().includes(query.toLowerCase())
    );

    displayUsers(filteredUsers);
}

function displayUsers(users) {
    const dropdown = document.getElementById('recipients-dropdown');
    
    if (users.length === 0) {
        dropdown.innerHTML = '<div class="no-users-found">No users found</div>';
    } else {
        dropdown.innerHTML = users.map(user => {
            const isSelected = selectedUsers.some(selected => selected.id === user.id);
            return `
                <div class="user-option ${isSelected ? 'selected' : ''}" data-user-id="${user.id}">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            ${user.avatar ? 
                                `<img src="${user.avatar}" alt="${user.name}" class="rounded-circle" width="32" height="32">` :
                                `<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 14px; font-weight: bold;">${user.name.charAt(0).toUpperCase()}</div>`
                            }
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${user.name}</div>
                            <small class="text-muted">${user.role}</small>
                        </div>
                        ${isSelected ? '<i class="bi bi-check-circle-fill text-primary"></i>' : ''}
                    </div>
                </div>
            `;
        }).join('');
    }
    
    showDropdown();
    
    // Add click event listeners to user options
    dropdown.querySelectorAll('.user-option').forEach(option => {
        option.addEventListener('click', function() {
            const userId = parseInt(this.dataset.userId);
            const user = allUsers.find(u => u.id === userId);
            
            if (user) {
                toggleUserSelection(user);
            }
        });
    });
}

function toggleUserSelection(user) {
    const existingIndex = selectedUsers.findIndex(selected => selected.id === user.id);
    
    if (existingIndex > -1) {
        // Remove user
        selectedUsers.splice(existingIndex, 1);
    } else {
        // Add user
        selectedUsers.push(user);
    }
    
    updateSelectedRecipients();
    updateDropdownSelection();
}

function updateSelectedRecipients() {
    const container = document.getElementById('selected-recipients');
    
    if (selectedUsers.length === 0) {
        container.innerHTML = '';
        return;
    }
    
    container.innerHTML = selectedUsers.map(user => `
        <span class="selected-recipient">
            ${user.name}
            <button type="button" class="remove-btn" onclick="removeUser(${user.id})">
                <i class="bi bi-x"></i>
            </button>
        </span>
    `).join('');
}

function removeUser(userId) {
    selectedUsers = selectedUsers.filter(user => user.id !== userId);
    updateSelectedRecipients();
    updateDropdownSelection();
}

function updateDropdownSelection() {
    const dropdown = document.getElementById('recipients-dropdown');
    dropdown.querySelectorAll('.user-option').forEach(option => {
        const userId = parseInt(option.dataset.userId);
        const isSelected = selectedUsers.some(selected => selected.id === userId);
        
        if (isSelected) {
            option.classList.add('selected');
            option.querySelector('.bi-check-circle-fill')?.remove();
            const iconContainer = option.querySelector('.d-flex');
            iconContainer.innerHTML += '<i class="bi bi-check-circle-fill text-primary"></i>';
        } else {
            option.classList.remove('selected');
            option.querySelector('.bi-check-circle-fill')?.remove();
        }
    });
}

function showDropdown() {
    document.getElementById('recipients-dropdown').style.display = 'block';
}

function hideDropdown() {
    document.getElementById('recipients-dropdown').style.display = 'none';
}

// Submit new message
document.getElementById('new-message-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (selectedUsers.length === 0) {
        alert('Please select at least one recipient.');
        return;
    }
    
    const formData = {
        recipient_ids: selectedUsers.map(user => user.id),
        subject: document.getElementById('subject').value,
        message: document.getElementById('message').value,
    };

    axios.post('/messages', formData, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => {
        if (response.data.success) {
            bootstrap.Modal.getInstance(newMessageModal).hide();
            window.location.href = `/messages/${response.data.data.conversation_id}`;
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        alert('Failed to send message. Please try again.');
    });
});
</script>
@endpush
@endsection
