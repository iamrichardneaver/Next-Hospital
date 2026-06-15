/**
 * Application Configuration Service
 * 
 * Provides dynamic URL generation and configuration management
 * for the NextHospital system across different environments.
 */

class AppConfig {
    constructor() {
        this.baseUrl = window.location.origin;
        this.appPath = this.detectAppPath();
        this.apiUrl = this.buildApiUrl();
        this.assetUrl = this.buildAssetUrl();
        this.wsUrl = this.buildWebSocketUrl();
        this.isInitialized = false;
        
        this.init();
    }

    /**
     * Initialize the configuration
     */
    init() {
        // Detect environment and set appropriate paths
        this.detectEnvironment();
        
        // Set up URL builders
        this.setupUrlBuilders();
        
        this.isInitialized = true;
        console.log('App Config initialized:', {
            baseUrl: this.baseUrl,
            appPath: this.appPath,
            apiUrl: this.apiUrl,
            environment: this.environment
        });
    }

    /**
     * Detect the application path from current URL
     */
    detectAppPath() {
        const pathname = window.location.pathname;
        
        // Check for common patterns
        if (pathname.includes('/nexthospital/backend/public/')) {
            return '/nexthospital/backend/public';
        } else if (pathname.includes('/backend/public/')) {
            return '/backend/public';
        } else if (pathname.includes('/public/')) {
            return '/public';
        } else if (pathname.includes('/nexthospital/')) {
            return '/nexthospital';
        } else {
            // For production or subdomain setups
            return '';
        }
    }

    /**
     * Detect environment based on URL patterns
     */
    detectEnvironment() {
        const hostname = window.location.hostname;
        
        if (hostname === 'localhost' || hostname === '127.0.0.1') {
            this.environment = 'local';
        } else if (hostname.includes('staging') || hostname.includes('test')) {
            this.environment = 'staging';
        } else if (hostname.includes('dev') || hostname.includes('development')) {
            this.environment = 'development';
        } else {
            this.environment = 'production';
        }
    }

    /**
     * Build API URL dynamically
     */
    buildApiUrl() {
        if (this.appPath) {
            return `${this.baseUrl}${this.appPath}/api`;
        }
        return `${this.baseUrl}/api`;
    }

    /**
     * Build asset URL dynamically
     */
    buildAssetUrl() {
        if (this.appPath) {
            return `${this.baseUrl}${this.appPath}`;
        }
        return this.baseUrl;
    }

    /**
     * Build WebSocket URL dynamically
     */
    buildWebSocketUrl() {
        // For now, disable WebSocket and use polling only
        // This prevents invalid URL errors in different environments
        return null;
    }

    /**
     * Set up URL builder methods
     */
    setupUrlBuilders() {
        // API URL builder
        this.api = (endpoint = '') => {
            const cleanEndpoint = endpoint.startsWith('/') ? endpoint.substring(1) : endpoint;
            return `${this.apiUrl}/${cleanEndpoint}`;
        };

        // Asset URL builder
        this.asset = (path = '') => {
            const cleanPath = path.startsWith('/') ? path.substring(1) : path;
            return `${this.assetUrl}/${cleanPath}`;
        };

        // Route URL builder
        this.route = (route = '') => {
            const cleanRoute = route.startsWith('/') ? route.substring(1) : route;
            if (this.appPath) {
                return `${this.baseUrl}${this.appPath}/${cleanRoute}`;
            }
            return `${this.baseUrl}/${cleanRoute}`;
        };

        // Web URL builder (for public routes)
        this.web = (route = '') => {
            const cleanRoute = route.startsWith('/') ? route.substring(1) : route;
            return `${this.baseUrl}/${cleanRoute}`;
        };
    }

    /**
     * Get CSRF token from meta tag
     */
    getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    /**
     * Get common headers for API requests
     */
    getApiHeaders(additionalHeaders = {}) {
        return {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': this.getCsrfToken(),
            'Accept': 'application/json',
            ...additionalHeaders
        };
    }

    /**
     * Get common fetch options for API requests
     */
    getApiFetchOptions(method = 'GET', body = null, additionalHeaders = {}) {
        const options = {
            method: method,
            headers: this.getApiHeaders(additionalHeaders),
            credentials: 'same-origin'
        };

        if (body && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
            options.body = typeof body === 'string' ? body : JSON.stringify(body);
        }

        return options;
    }

    /**
     * Make API request with automatic URL building
     */
    async apiRequest(endpoint, options = {}) {
        const url = this.api(endpoint);
        const fetchOptions = this.getApiFetchOptions(
            options.method || 'GET',
            options.body,
            options.headers
        );

        try {
            const response = await fetch(url, fetchOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return response;
        } catch (error) {
            console.error(`API request failed for ${endpoint}:`, error);
            throw error;
        }
    }

    /**
     * Get configuration value
     */
    get(key, defaultValue = null) {
        const config = {
            baseUrl: this.baseUrl,
            appPath: this.appPath,
            apiUrl: this.apiUrl,
            assetUrl: this.assetUrl,
            wsUrl: this.wsUrl,
            environment: this.environment
        };

        return config[key] !== undefined ? config[key] : defaultValue;
    }

    /**
     * Check if running in specific environment
     */
    isEnvironment(env) {
        return this.environment === env;
    }

    /**
     * Check if running locally
     */
    isLocal() {
        return this.isEnvironment('local');
    }

    /**
     * Check if running in production
     */
    isProduction() {
        return this.isEnvironment('production');
    }

    /**
     * Get debug information
     */
    getDebugInfo() {
        return {
            baseUrl: this.baseUrl,
            appPath: this.appPath,
            apiUrl: this.apiUrl,
            assetUrl: this.assetUrl,
            wsUrl: this.wsUrl,
            environment: this.environment,
            pathname: window.location.pathname,
            hostname: window.location.hostname,
            port: window.location.port
        };
    }
}

// Create global instance
let appConfig = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    appConfig = new AppConfig();
    window.appConfig = appConfig;
    console.log('App Config is ready');
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AppConfig;
}
