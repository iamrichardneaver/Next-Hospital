<!-- Emergency Alert Notification Component -->
<div id="emergencyAlertNotification" class="position-fixed top-0 end-0 p-3" style="z-index: 9999; max-width: 400px;">
    <!-- Alert notifications will be dynamically inserted here -->
</div>

<!-- Emergency Alert Sound -->
<audio id="emergencyAlertSound" preload="auto" onerror="console.warn('Emergency alert audio file could not be loaded')">
    <source src="{{ asset('assets/sounds/emergency-alert.mp3') }}" type="audio/mpeg">
    <source src="{{ asset('assets/sounds/emergency-alert.wav') }}" type="audio/wav">
</audio>

<script>
class EmergencyAlertNotification {
    constructor() {
        this.notifications = [];
        this.maxNotifications = 5;
        this.audioEnabled = true;
        this.init();
    }

    init() {
        // Check if user has granted audio permissions
        this.checkAudioPermissions();
        
        // Listen for emergency alert events
        this.listenForAlerts();
        
        // Auto-refresh active alerts every 30 seconds
        setInterval(() => this.refreshActiveAlerts(), 30000);
        
        // Initial load of active alerts
        this.loadActiveAlerts();
    }

    checkAudioPermissions() {
        // Request audio permissions
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(() => {
                    this.audioEnabled = true;
                })
                .catch(() => {
                    this.audioEnabled = false;
                    console.log('Audio permissions denied');
                });
        }
    }

    listenForAlerts() {
        // Listen for Laravel Echo events
        if (typeof Echo !== 'undefined') {
            Echo.channel('emergency-alerts')
                .listen('EmergencyAlertSent', (e) => {
                    this.handleNewAlert(e.alert);
                });
            
            Echo.channel('hospital-notifications')
                .listen('EmergencyAlertSent', (e) => {
                    this.handleNewAlert(e.alert);
                });
        }
    }

    handleNewAlert(alertData) {
        // Play sound for critical/urgent alerts
        if (this.shouldPlaySound(alertData.priority)) {
            this.playAlertSound();
        }

        // Add notification to display
        this.addNotification(alertData);
    }

    shouldPlaySound(priority) {
        return this.audioEnabled && ['critical', 'urgent'].includes(priority);
    }

    playAlertSound() {
        const audio = document.getElementById('emergencyAlertSound');
        if (audio) {
            audio.currentTime = 0;
            audio.play().catch(e => {
                // Silently handle audio play errors - don't spam console
                console.warn('Emergency alert audio could not be played:', e.message);
                // Disable audio for future attempts to avoid repeated errors
                this.audioEnabled = false;
            });
        }
    }

    addNotification(alertData) {
        const notificationId = `alert-${alertData.id}`;
        
        // Remove existing notification with same ID
        this.removeNotification(notificationId);
        
        // Create notification element
        const notification = this.createNotificationElement(alertData, notificationId);
        
        // Add to container
        const container = document.getElementById('emergencyAlertNotification');
        container.insertBefore(notification, container.firstChild);
        
        // Limit number of notifications
        this.limitNotifications();
        
        // Auto-remove after 10 seconds for non-critical alerts
        if (!['critical', 'urgent'].includes(alertData.priority)) {
            setTimeout(() => this.removeNotification(notificationId), 10000);
        }
    }

    createNotificationElement(alertData, notificationId) {
        const priorityClass = this.getPriorityClass(alertData.priority);
        const statusClass = this.getStatusClass(alertData.status);
        
        const notification = document.createElement('div');
        notification.id = notificationId;
        notification.className = `alert alert-${priorityClass} alert-dismissible fade show mb-2 emergency-notification`;
        notification.style.cssText = `
            animation: slideInRight 0.5s ease-out;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-left: 4px solid var(--bs-${priorityClass});
        `;
        
        notification.innerHTML = `
            <div class="d-flex align-items-start">
                <div class="me-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <h6 class="mb-0 fw-bold">Emergency Alert</h6>
                        <span class="badge bg-${priorityClass}">${alertData.priority.toUpperCase()}</span>
                    </div>
                    <p class="mb-1 small">${alertData.message}</p>
                    ${alertData.patient ? `
                        <div class="small text-muted">
                            <i class="bi bi-person"></i> ${alertData.patient.name} (${alertData.patient.patient_number})
                        </div>
                    ` : ''}
                    <div class="small text-muted">
                        <i class="bi bi-clock"></i> ${this.formatTime(alertData.created_at)}
                    </div>
                </div>
                <div class="ms-2">
                    <button type="button" class="btn-close btn-close-white" onclick="emergencyAlertNotification.removeNotification('${notificationId}')"></button>
                </div>
            </div>
            <div class="mt-2">
                <div class="btn-group btn-group-sm w-100">
                    <button class="btn btn-outline-light btn-sm" onclick="emergencyAlertNotification.acknowledgeAlert(${alertData.id})">
                        <i class="bi bi-check-circle"></i> Acknowledge
                    </button>
                    <button class="btn btn-outline-light btn-sm" onclick="emergencyAlertNotification.viewAlert(${alertData.id})">
                        <i class="bi bi-eye"></i> View
                    </button>
                </div>
            </div>
        `;
        
        return notification;
    }

    getPriorityClass(priority) {
        switch (priority) {
            case 'critical': return 'danger';
            case 'urgent': return 'warning';
            case 'high': return 'info';
            case 'medium': return 'primary';
            case 'low': return 'secondary';
            default: return 'info';
        }
    }

    getStatusClass(status) {
        switch (status) {
            case 'active': return 'danger';
            case 'acknowledged': return 'warning';
            case 'resolved': return 'success';
            default: return 'info';
        }
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString();
    }

    removeNotification(notificationId) {
        const notification = document.getElementById(notificationId);
        if (notification) {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }

    limitNotifications() {
        const container = document.getElementById('emergencyAlertNotification');
        const notifications = container.querySelectorAll('.emergency-notification');
        
        if (notifications.length > this.maxNotifications) {
            for (let i = this.maxNotifications; i < notifications.length; i++) {
                this.removeNotification(notifications[i].id);
            }
        }
    }

    async acknowledgeAlert(alertId) {
        try {
            const response = await fetch(`/api/emergency-alerts/${alertId}/acknowledge`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                this.removeNotification(`alert-${alertId}`);
                this.showToast('Alert acknowledged successfully', 'success');
            } else {
                // Handle authentication errors gracefully
                if (response.status === 401 || response.status === 403) {
                    this.showToast('You do not have permission to acknowledge alerts', 'error');
                } else {
                    this.showToast('Failed to acknowledge alert', 'error');
                }
            }
        } catch (error) {
            console.error('Error acknowledging alert:', error);
            this.showToast('Error acknowledging alert', 'error');
        }
    }

    viewAlert(alertId) {
        window.open(`/emergency-alerts/${alertId}`, '_blank');
    }

    async loadActiveAlerts() {
        try {
            // Use direct URL - route is defined in API routes
            const alertsUrl = '/api/emergency-alerts/active';
            const response = await fetch(alertsUrl, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                // Silently handle all error statuses - don't log to console
                // 401/403: User not authenticated or no permission
                // 404: Route not found or not accessible
                // Other errors: Network or server issues
                return;
            }
            
            const data = await response.json();
            
            if (data.success && data.data && data.data.length > 0) {
                data.data.forEach(alert => {
                    if (alert.status === 'active') {
                        this.addNotification(alert);
                    }
                });
            }
        } catch (error) {
            // Silently handle errors - don't log 404, 401, 403, or network errors
            if (error.message && !error.message.includes('401') && !error.message.includes('403') && !error.message.includes('404')) {
                // Only log unexpected errors
                console.warn('Emergency alerts: Failed to load alerts -', error.message);
            }
            // Don't show error to user, just handle silently
        }
    }

    async refreshActiveAlerts() {
        // Only refresh if there are active notifications
        const container = document.getElementById('emergencyAlertNotification');
        const activeNotifications = container.querySelectorAll('.emergency-notification');
        
        if (activeNotifications.length > 0) {
            this.loadActiveAlerts();
        }
    }

    showToast(message, type = 'info') {
        // Simple toast notification
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            document.body.removeChild(toast);
        });
    }
}

// Initialize emergency alert notification system
const emergencyAlertNotification = new EmergencyAlertNotification();

// CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .emergency-notification {
        transition: all 0.3s ease;
    }
    
    .emergency-notification:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    }
`;
document.head.appendChild(style);
</script>
