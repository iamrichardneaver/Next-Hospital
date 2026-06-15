/**
 * Real-Time Data Service for Blade Frontend
 * 
 * Handles intelligent polling, WebSocket connections, and smart caching
 * for real-time data updates across all modules.
 */

class RealTimeDataService {
    constructor() {
        // Wait for app config to be available
        this.waitForAppConfig().then(() => {
            this.baseUrl = window.appConfig.baseUrl;
            this.apiUrl = window.appConfig.apiUrl;
            this.wsUrl = window.appConfig.wsUrl;
            this.wsConnection = null;
            this.pollingIntervals = new Map();
            this.moduleConfigs = new Map();
            this.dataCache = new Map();
            this.changeListeners = new Map();
            this.isOnline = navigator.onLine;
            this.retryCount = 0;
            this.maxRetries = 3;
            this.backoffMultiplier = 1.5;
            this.maxBackoff = 30000; // 30 seconds
            this.isInitialized = false;
            this.isAuthenticated = false;
            
            this.init().catch(error => {
                console.log('Real-Time Data Service initialization failed:', error.message);
            });
        });
    }

    /**
     * Wait for app config to be available
     */
    async waitForAppConfig() {
        return new Promise((resolve) => {
            if (window.appConfig) {
                resolve();
            } else {
                const checkConfig = () => {
                    if (window.appConfig) {
                        resolve();
                    } else {
                        setTimeout(checkConfig, 100);
                    }
                };
                checkConfig();
            }
        });
    }

    /**
     * Initialize the service
     */
    async init() {
        if (this.isInitialized) {
            return;
        }

        this.initializeEventListeners();
        this.initializeWebSocket();
        
        // Only load user modules if user is authenticated
        try {
            await this.checkAuthentication();
            await this.loadUserModules();
        } catch (error) {
            console.log('User not authenticated - skipping real-time data initialization');
            this.isAuthenticated = false;
            return;
        }
        
        this.isAuthenticated = true;
        
        // Set up activity tracking now that we're authenticated
        this.trackUserActivity();
        
        this.isInitialized = true;
        console.log('Real-Time Data Service initialized');
    }

    /**
     * Check if user is authenticated
     */
    async checkAuthentication() {
        // First, try to check if user is authenticated without making API calls
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            console.log('No CSRF token found - user likely not authenticated');
            throw new Error('User not authenticated');
        }

        try {
            const response = await fetch(`${this.apiUrl}/realtime/active-modules`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });

            if (response.status === 401) {
                throw new Error('User not authenticated');
            }

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return true;
        } catch (error) {
            console.log('Authentication check failed:', error.message);
            throw error;
        }
    }

    /**
     * Initialize event listeners
     */
    initializeEventListeners() {
        // Online/offline detection
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.retryCount = 0;
            this.reconnectWebSocket();
            this.resumePolling();
            console.log('Connection restored - resuming real-time updates');
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.disconnectWebSocket();
            this.pausePolling();
            console.log('Connection lost - pausing real-time updates');
        });

        // Visibility change detection
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pausePolling();
            } else {
                this.resumePolling();
                this.refreshAllModules();
            }
        });

        // Page focus to refresh data
        window.addEventListener('focus', () => {
            this.refreshAllModules();
        });

        // User activity tracking will be set up after authentication check
    }

    /**
     * Track user activity for intelligent polling
     */
    trackUserActivity() {
        // Only track activity if user is authenticated
        if (!this.isAuthenticated) {
            return;
        }

        const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];
        
        // Store the bound function for later removal
        this.updateActivityHandler = () => {
            this.updateActivity();
        };

        activityEvents.forEach(event => {
            document.addEventListener(event, this.updateActivityHandler, { passive: true });
        });
    }


    /**
     * Initialize WebSocket connection
     */
    initializeWebSocket() {
        if (!this.isOnline || !this.wsUrl) {
            console.log('WebSocket disabled - using polling only');
            return;
        }

        try {
            this.wsConnection = new WebSocket(this.wsUrl);
            
            this.wsConnection.onopen = () => {
                console.log('WebSocket connected');
                this.retryCount = 0;
                this.authenticateWebSocket();
            };

            this.wsConnection.onmessage = (event) => {
                this.handleWebSocketMessage(event);
            };

            this.wsConnection.onclose = () => {
                console.log('WebSocket disconnected');
                this.scheduleReconnect();
            };

            this.wsConnection.onerror = (error) => {
                console.error('WebSocket error:', error);
                this.scheduleReconnect();
            };

        } catch (error) {
            console.error('Failed to initialize WebSocket:', error);
            this.scheduleReconnect();
        }
    }

    /**
     * Authenticate WebSocket connection
     */
    async authenticateWebSocket() {
        if (!this.wsConnection || this.wsConnection.readyState !== WebSocket.OPEN) {
            return;
        }

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (token) {
                this.wsConnection.send(JSON.stringify({
                    type: 'auth',
                    token: token
                }));
            }
        } catch (error) {
            console.error('Failed to authenticate WebSocket:', error);
        }
    }

    /**
     * Handle WebSocket messages
     */
    handleWebSocketMessage(event) {
        try {
            const message = JSON.parse(event.data);
            
            switch (message.type) {
                case 'data.changed':
                    this.handleDataChanged(message);
                    break;
                case 'notification':
                    this.handleNotification(message);
                    break;
                case 'auth.success':
                    console.log('WebSocket authenticated');
                    break;
                case 'auth.error':
                    console.error('WebSocket authentication failed:', message.error);
                    break;
                default:
                    console.log('Unknown WebSocket message:', message);
            }
        } catch (error) {
            console.error('Failed to parse WebSocket message:', error);
        }
    }

    /**
     * Handle data changed events
     */
    handleDataChanged(message) {
        const { module, data, change_type } = message;
        
        // Update cache
        if (data) {
            this.dataCache.set(module, data);
        }

        // Notify listeners
        const listeners = this.changeListeners.get(module);
        if (listeners) {
            listeners.forEach(listener => {
                try {
                    listener(data);
                } catch (error) {
                    console.error('Error in data change listener:', error);
                }
            });
        }

        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('realtime:data-changed', {
            detail: { module, data, change_type }
        }));

        // Show visual indicator
        this.showDataUpdateIndicator(module);
    }

    /**
     * Handle notification events
     */
    handleNotification(message) {
        window.dispatchEvent(new CustomEvent('realtime:notification', {
            detail: message
        }));
    }

    /**
     * Show visual indicator for data updates
     */
    showDataUpdateIndicator(module) {
        // Find elements that might need updating
        const indicators = document.querySelectorAll(`[data-module="${module}"]`);
        
        indicators.forEach(element => {
            element.classList.add('data-updated');
            setTimeout(() => {
                element.classList.remove('data-updated');
            }, 2000);
        });

        // Show toast notification for important updates
        if (['emergency_alerts', 'queue', 'lab_results'].includes(module)) {
            this.showUpdateToast(module);
        }
    }

    /**
     * Show update toast notification
     */
    showUpdateToast(module) {
        const moduleNames = {
            'emergency_alerts': 'Emergency Alerts',
            'queue': 'Queue',
            'lab_results': 'Lab Results',
            'prescriptions': 'Prescriptions',
            'billing': 'Billing',
            'patients': 'Patients',
            'appointments': 'Appointments',
            'wards': 'Wards',
            'pharmacy': 'Pharmacy'
        };

        const moduleName = moduleNames[module] || module;
        
        // Create toast
        const toastId = 'realtime-update-toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = 'toast realtime-update-toast';
        toast.setAttribute('role', 'alert');
        
        toast.innerHTML = `
            <div class="toast-header bg-primary text-white">
                <i class="bi bi-arrow-clockwise me-2"></i>
                <strong class="me-auto">Data Updated</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${moduleName} data has been refreshed
            </div>
        `;
        
        // Add to container
        let container = document.getElementById('realtime-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'realtime-toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        container.appendChild(toast);
        
        // Initialize Bootstrap toast
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 3000
        });
        bsToast.show();
        
        // Remove toast after hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    /**
     * Schedule WebSocket reconnection
     */
    scheduleReconnect() {
        if (this.retryCount >= this.maxRetries) {
            console.log('Max reconnection attempts reached');
            return;
        }

        const delay = Math.min(
            this.maxBackoff,
            Math.pow(this.backoffMultiplier, this.retryCount) * 1000
        );

        setTimeout(() => {
            this.retryCount++;
            this.initializeWebSocket();
        }, delay);
    }

    /**
     * Reconnect WebSocket
     */
    reconnectWebSocket() {
        this.disconnectWebSocket();
        this.initializeWebSocket();
    }

    /**
     * Disconnect WebSocket
     */
    disconnectWebSocket() {
        if (this.wsConnection) {
            this.wsConnection.close();
            this.wsConnection = null;
        }
    }

    /**
     * Load user's active modules
     */
    async loadUserModules() {
        try {
            const response = await fetch(`${this.apiUrl}/realtime/active-modules`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                if (response.status === 401) {
                    console.log('User not authenticated - skipping real-time data loading');
                    return;
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success) {
                // Register modules with intelligent polling
                for (const module of data.modules) {
                    await this.registerModule(module);
                }
            }

        } catch (error) {
            console.error('Failed to load user modules:', error);
        }
    }

    /**
     * Register a module for real-time updates
     */
    async registerModule(module, customConfig = {}) {
        try {
            // Get polling interval for this module
            const interval = await this.getPollingInterval(module);
            
            const config = {
                module: module,
                priority: this.getModulePriority(module),
                pollingInterval: interval,
                filters: customConfig.filters || {},
                enabled: customConfig.enabled !== false
            };

            this.moduleConfigs.set(module, config);
            
            if (config.enabled) {
                this.startPolling(module);
            }

            console.log(`Registered module: ${module} with ${interval}ms interval`);

        } catch (error) {
            console.error(`Failed to register module ${module}:`, error);
        }
    }

    /**
     * Get module priority for polling
     */
    getModulePriority(module) {
        const priorities = {
            'emergency_alerts': 1,
            'queue': 2,
            'lab_results': 3,
            'prescriptions': 4,
            'wards': 5,
            'appointments': 6,
            'patients': 7,
            'billing': 8,
            'pharmacy': 9
        };

        return priorities[module] || 10;
    }

    /**
     * Start polling for a module
     */
    startPolling(module) {
        const config = this.moduleConfigs.get(module);
        if (!config || !config.enabled) {
            return;
        }

        // Stop existing polling for this module
        this.stopPolling(module);

        const poll = async () => {
            try {
                await this.pollModuleData(module);
            } catch (error) {
                console.error(`Error polling module ${module}:`, error);
            }
        };

        // Initial poll
        poll();

        // Set up interval
        const interval = setInterval(poll, config.pollingInterval);
        this.pollingIntervals.set(module, interval);
    }

    /**
     * Stop polling for a module
     */
    stopPolling(module) {
        const interval = this.pollingIntervals.get(module);
        if (interval) {
            clearInterval(interval);
            this.pollingIntervals.delete(module);
        }
    }

    /**
     * Poll module data
     */
    async pollModuleData(module) {
        if (!this.isOnline) {
            return;
        }

        const config = this.moduleConfigs.get(module);
        if (!config) {
            return;
        }

        try {
            const lastCheck = this.getLastCheckTime(module);
            const response = await fetch(`${this.apiUrl}/realtime/module-data`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    module: module,
                    filters: config.filters || {},
                    last_check: lastCheck
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success && data.has_changes) {
                this.handleModuleDataUpdate(module, data.data);
            }

            // Update last check time
            this.setLastCheckTime(module, data.timestamp);

        } catch (error) {
            console.error(`Failed to poll module ${module}:`, error);
        }
    }

    /**
     * Handle module data update
     */
    handleModuleDataUpdate(module, data) {
        // Update cache
        this.dataCache.set(module, data);

        // Notify listeners
        const listeners = this.changeListeners.get(module);
        if (listeners) {
            listeners.forEach(listener => {
                try {
                    listener(data);
                } catch (error) {
                    console.error('Error in module data listener:', error);
                }
            });
        }

        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('realtime:module-updated', {
            detail: { module, data }
        }));

        // Update UI elements
        this.updateUIElements(module, data);
    }

    /**
     * Update UI elements with new data
     */
    updateUIElements(module, data) {
        // Find elements that need updating
        const elements = document.querySelectorAll(`[data-realtime-module="${module}"]`);
        
        elements.forEach(element => {
            const updateType = element.dataset.realtimeUpdate || 'replace';
            
            switch (updateType) {
                case 'replace':
                    this.replaceElementContent(element, data);
                    break;
                case 'append':
                    this.appendElementContent(element, data);
                    break;
                case 'prepend':
                    this.prependElementContent(element, data);
                    break;
                case 'count':
                    this.updateElementCount(element, data);
                    break;
                case 'badge':
                    this.updateElementBadge(element, data);
                    break;
            }
        });
    }

    /**
     * Replace element content
     */
    replaceElementContent(element, data) {
        if (data.data && Array.isArray(data.data)) {
            // Update table rows
            if (element.tagName === 'TBODY') {
                this.updateTableBody(element, data.data);
            } else {
                element.innerHTML = this.generateContent(data.data, element.dataset.realtimeTemplate);
            }
        }
    }

    /**
     * Update table body with new data
     */
    updateTableBody(tbody, data) {
        // Store current scroll position
        const scrollTop = tbody.closest('.table-responsive')?.scrollTop || 0;
        
        // Clear existing rows
        tbody.innerHTML = '';
        
        // Add new rows
        data.forEach(item => {
            const row = this.createTableRow(item, tbody.dataset.realtimeTemplate);
            tbody.appendChild(row);
        });
        
        // Restore scroll position
        const tableContainer = tbody.closest('.table-responsive');
        if (tableContainer) {
            tableContainer.scrollTop = scrollTop;
        }
    }

    /**
     * Create table row from data
     */
    createTableRow(item, template) {
        const row = document.createElement('tr');
        
        if (template) {
            // Use custom template
            row.innerHTML = this.processTemplate(template, item);
        } else {
            // Generate default row
            row.innerHTML = this.generateDefaultTableRow(item);
        }
        
        return row;
    }

    /**
     * Process template with data
     */
    processTemplate(template, data) {
        return template.replace(/\{\{(\w+)\}\}/g, (match, key) => {
            return data[key] || '';
        });
    }

    /**
     * Generate default table row
     */
    generateDefaultTableRow(item) {
        const cells = Object.values(item).slice(0, 5); // First 5 fields
        return cells.map(value => `<td>${value || ''}</td>`).join('');
    }

    /**
     * Update element count
     */
    updateElementCount(element, data) {
        const count = data.total_count || (data.data ? data.data.length : 0);
        element.textContent = count;
    }

    /**
     * Update element badge
     */
    updateElementBadge(element, data) {
        const count = data.total_count || (data.data ? data.data.length : 0);
        element.textContent = count;
        
        // Update badge class based on count
        element.className = 'badge';
        if (count > 0) {
            element.classList.add('bg-danger');
        } else {
            element.classList.add('bg-secondary');
        }
    }

    /**
     * Add listener for module data changes
     */
    addDataChangeListener(module, listener) {
        if (!this.changeListeners.has(module)) {
            this.changeListeners.set(module, new Set());
        }
        this.changeListeners.get(module).add(listener);
    }

    /**
     * Remove listener for module data changes
     */
    removeDataChangeListener(module, listener) {
        const listeners = this.changeListeners.get(module);
        if (listeners) {
            listeners.delete(listener);
        }
    }

    /**
     * Get cached data for module
     */
    getCachedData(module) {
        return this.dataCache.get(module) || null;
    }

    /**
     * Refresh all modules
     */
    async refreshAllModules() {
        // Don't refresh if not authenticated
        if (!this.isAuthenticated) {
            return;
        }

        const modules = Array.from(this.moduleConfigs.keys());
        
        for (const module of modules) {
            try {
                await this.pollModuleData(module);
            } catch (error) {
                console.error(`Failed to refresh module ${module}:`, error);
            }
        }
    }

    /**
     * Pause all polling
     */
    pausePolling() {
        this.pollingIntervals.forEach((interval, module) => {
            clearInterval(interval);
        });
        this.pollingIntervals.clear();
    }

    /**
     * Resume all polling
     */
    resumePolling() {
        this.moduleConfigs.forEach((config, module) => {
            if (config.enabled) {
                this.startPolling(module);
            }
        });
    }

    /**
     * Get polling interval for module
     */
    async getPollingInterval(module) {
        try {
            const response = await fetch(`${this.apiUrl}/realtime/polling-interval`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                credentials: 'same-origin',
                body: JSON.stringify({ module })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data.interval || 30000;

        } catch (error) {
            console.error('Failed to get polling interval:', error);
            return 30000; // Default 30 seconds
        }
    }

    /**
     * Update user activity
     */
    async updateActivity() {
        // Don't try to update activity if user is not authenticated
        if (!this.isAuthenticated) {
            return;
        }

        try {
            const response = await fetch(`${this.apiUrl}/realtime/update-activity`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                if (response.status === 401) {
                    // User not authenticated, stop activity tracking
                    this.isAuthenticated = false;
                    this.stopActivityTracking();
                    return;
                }
                if (response.status === 404) {
                    // Endpoint not found, stop activity tracking to prevent spam
                    console.warn('Activity tracking endpoint not found - disabling activity tracking');
                    this.stopActivityTracking();
                    return;
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
        } catch (error) {
            console.error('Failed to update activity:', error);
            // Stop activity tracking on persistent errors to prevent spam
            if (error.message.includes('404')) {
                this.stopActivityTracking();
            }
        }
    }

    /**
     * Stop activity tracking
     */
    stopActivityTracking() {
        // Remove activity event listeners
        const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];
        activityEvents.forEach(event => {
            document.removeEventListener(event, this.updateActivityHandler);
        });
    }

    /**
     * Get last check time for module
     */
    getLastCheckTime(module) {
        const key = `last_check_${module}`;
        return localStorage.getItem(key);
    }

    /**
     * Set last check time for module
     */
    setLastCheckTime(module, timestamp) {
        const key = `last_check_${module}`;
        localStorage.setItem(key, timestamp);
    }

    /**
     * Invalidate cache for module
     */
    async invalidateCache(module) {
        try {
            await fetch(`${this.apiUrl}/realtime/invalidate-cache`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                credentials: 'same-origin',
                body: JSON.stringify({ module })
            });

            // Clear local cache
            this.dataCache.delete(module);

        } catch (error) {
            console.error('Failed to invalidate cache:', error);
        }
    }

    /**
     * Cleanup service
     */
    destroy() {
        this.pausePolling();
        this.disconnectWebSocket();
        this.changeListeners.clear();
        this.moduleConfigs.clear();
        this.dataCache.clear();
    }
}

// Create global instance
let realTimeDataService = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('meta[name="csrf-token"]')) {
        realTimeDataService = new RealTimeDataService();
        window.realTimeDataService = realTimeDataService;
        console.log('Real-Time Data Service is ready');
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RealTimeDataService;
}
