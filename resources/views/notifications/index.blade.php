@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="bi bi-bell text-primary me-2"></i>
                Notifications
            </h2>
            <p class="text-muted mb-0">View and manage your notifications</p>
        </div>
        <div>
            @if($notifications->total() > 0)
                <button type="button" class="btn btn-outline-primary" id="mark-all-read-btn">
                    <i class="bi bi-check-all me-2"></i>Mark All as Read
                </button>
                <button type="button" class="btn btn-outline-danger" id="clear-all-btn">
                    <i class="bi bi-trash me-2"></i>Clear All
                </button>
            @endif
        </div>
    </div>

    <!-- Notification Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filter by Status</label>
                    <select class="form-select" id="status-filter">
                        <option value="">All Notifications</option>
                        <option value="unread">Unread Only</option>
                        <option value="read">Read Only</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filter by Type</label>
                    <select class="form-select" id="type-filter">
                        <option value="">All Types</option>
                        <option value="appointment">Appointments</option>
                        <option value="consultation">Consultations</option>
                        <option value="lab">Lab Results</option>
                        <option value="prescription">Prescriptions</option>
                        <option value="system">System</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-primary d-block w-100" id="apply-filter-btn">
                        <i class="bi bi-funnel me-2"></i>Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="card">
        <div class="card-body">
            @forelse($notifications as $notification)
                <div class="notification-item {{ $notification->read_at ? 'read' : 'unread' }}" data-id="{{ $notification->id }}">
                    <div class="d-flex">
                        <div class="notification-icon me-3">
                            <i class="bi bi-{{ $notification->data['icon'] ?? 'bell' }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">{{ $notification->data['title'] ?? 'Notification' }}</h6>
                                <div class="notification-actions">
                                    @if(!$notification->read_at)
                                        <button type="button" class="btn btn-sm btn-outline-primary mark-read-btn" data-id="{{ $notification->id }}" title="Mark as read">
                                            <i class="bi bi-check"></i>
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-id="{{ $notification->id }}" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="mb-2 text-muted">{{ $notification->data['message'] ?? '' }}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
                                </small>
                                @if(isset($notification->data['url']))
                                    <a href="{{ $notification->data['url'] }}" class="btn btn-sm btn-primary">View Details</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="bi bi-bell-slash text-muted" style="font-size: 4rem;"></i>
                    <p class="text-muted mt-3 mb-0">No notifications found</p>
                </div>
            @endforelse

            <!-- Pagination -->
            @if($notifications->hasPages())
                <div class="mt-4">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
.notification-item {
    padding: 1rem;
    border-bottom: 1px solid #e0e0e0;
    transition: background-color 0.2s;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.unread {
    background-color: #f0f8ff;
}

.notification-item:hover {
    background-color: #f5f5f5;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #007bff;
    color: white;
}

.notification-item.unread .notification-icon {
    background-color: #28a745;
}

.notification-actions {
    display: flex;
    gap: 0.5rem;
}
</style>
@endpush

@push('scripts')
<script>
// Mark notification as read
document.querySelectorAll('.mark-read-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const notificationId = this.dataset.id;
        markAsRead(notificationId);
    });
});

// Delete notification
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const notificationId = this.dataset.id;
        deleteNotification(notificationId);
    });
});

// Mark all as read
document.getElementById('mark-all-read-btn')?.addEventListener('click', function() {
    markAllAsRead();
});

// Clear all notifications
document.getElementById('clear-all-btn')?.addEventListener('click', function() {
    if (confirm('Are you sure you want to clear all notifications? This action cannot be undone.')) {
        clearAll();
    }
});

function markAsRead(notificationId) {
    axios.post(`/notifications/${notificationId}/mark-read`, {}, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => {
        if (response.data.success) {
            const item = document.querySelector(`[data-id="${notificationId}"]`);
            if (item) {
                item.classList.remove('unread');
                item.classList.add('read');
                const btn = item.querySelector('.mark-read-btn');
                if (btn) btn.remove();
            }
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

function deleteNotification(notificationId) {
    axios.delete(`/notifications/${notificationId}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => {
        if (response.data.success) {
            const item = document.querySelector(`[data-id="${notificationId}"]`);
            if (item) {
                item.style.animation = 'fadeOut 0.3s';
                setTimeout(() => item.remove(), 300);
            }
        }
    })
    .catch(error => {
        console.error('Error deleting notification:', error);
    });
}

function markAllAsRead() {
    axios.post('/notifications/mark-all-read', {}, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => {
        if (response.data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
    });
}

function clearAll() {
    axios.delete('/notifications/clear-all', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => {
        if (response.data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error clearing notifications:', error);
    });
}
</script>
@endpush
@endsection
