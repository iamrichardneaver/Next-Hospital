/**
 * NextHospital - Main Application JavaScript
 * Handles AJAX calls, dynamic features, and UI interactions
 */

// Configure Axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// Get CSRF token from meta tag
if (typeof window.csrfToken === 'undefined') {
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}
if (window.csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = window.csrfToken;
}

// Add auth token if exists
const authToken = localStorage.getItem('auth_token');
if (authToken) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${authToken}`;
}

/**
 * Utility Functions
 */

// Debounce function for search inputs
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

// Show loading spinner
function showLoader() {
    const loader = `
        <div class="spinner-overlay" id="loader">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', loader);
}

// Hide loading spinner
function hideLoader() {
    const loader = document.getElementById('loader');
    if (loader) {
        loader.remove();
    }
}

// Show toast notification
function showToast(message, type = 'info') {
    const toast = `
        <div class="toast align-items-center text-white bg-${type} border-0 position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', toast);
    
    const toastElement = document.querySelector('.toast:last-child');
    const bsToast = new bootstrap.Toast(toastElement, { autohide: true, delay: 3000 });
    bsToast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: 'GHS'
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-GH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Format datetime
function formatDateTime(dateString) {
    return new Date(dateString).toLocaleString('en-GH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Sidebar Toggle
 */
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }
    
    // Set active menu item
    const currentPath = window.location.pathname;
    const currentFullUrl = window.location.pathname + window.location.search;
    
    // First, handle submenu items (do this first to catch nested items)
    const submenuLinks = document.querySelectorAll('.sidebar-submenu a');
    let submenuItemActive = false;
    let activeParentItem = null;
    
    submenuLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (!href) return;
        
        // Parse the href to get just the path
        let linkPath, linkFullUrl;
        try {
            const url = new URL(href, window.location.origin);
            linkPath = url.pathname;
            linkFullUrl = url.pathname + url.search;
        } catch (e) {
            linkPath = href.split('?')[0];
            linkFullUrl = href;
        }
        
        // Check for exact match (with or without query params)
        const isActive = currentFullUrl === linkFullUrl || 
                        (currentPath === linkPath && !link.hasAttribute('data-strict-match'));
        
        if (isActive) {
            link.classList.add('active');
            submenuItemActive = true;
            
            // Also expand the parent menu (but don't highlight it)
            const parentMenuItem = link.closest('.sidebar-menu-item.has-submenu');
            if (parentMenuItem) {
                activeParentItem = parentMenuItem;
                parentMenuItem.classList.add('active');
                // Don't add 'active' class to parent link - only expand the menu
            }
        }
    });
    
    // Then handle main menu items (always check, regardless of submenu state)
    const menuLinks = document.querySelectorAll('.sidebar-menu-item:not(.has-submenu) > .sidebar-menu-link');
    
    menuLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (!href) return;
        
        // Parse the href to get just the path
        let linkPath, linkFullUrl;
        try {
            const url = new URL(href, window.location.origin);
            linkPath = url.pathname;
            linkFullUrl = url.pathname + url.search;
        } catch (e) {
            linkPath = href.split('?')[0];
            linkFullUrl = href;
        }
        
        // Check for exact match (with or without query params)
        const isActive = currentFullUrl === linkFullUrl || 
                        (currentPath === linkPath && !link.hasAttribute('data-strict-match'));
        
        if (isActive) {
            link.classList.add('active');
        }
    });
    
    // Handle submenu toggle
    const submenuParents = document.querySelectorAll('.sidebar-menu-item.has-submenu > .sidebar-menu-link');
    submenuParents.forEach(parentLink => {
        parentLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            const parentItem = this.closest('.sidebar-menu-item.has-submenu');
            const allSubmenuItems = document.querySelectorAll('.sidebar-menu-item.has-submenu');
            
            // Don't close the menu if it has an active submenu item
            const hasActiveChild = parentItem.classList.contains('active') && activeParentItem === parentItem;
            
            // Close other submenus (except the one with active child)
            allSubmenuItems.forEach(item => {
                if (item !== parentItem && item !== activeParentItem) {
                    item.classList.remove('active');
                }
            });
            
            // Toggle current submenu only if it doesn't have an active child
            if (!hasActiveChild) {
                parentItem.classList.toggle('active');
            }
        });
    });
});

/**
 * Handle AJAX form submissions
 */
document.addEventListener('submit', function(e) {
    const form = e.target;
    
    if (form.classList.contains('ajax-form')) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const method = form.method.toUpperCase();
        const action = form.action;
        
        showLoader();
        
        axios({
            method: method,
            url: action,
            data: formData,
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
        .then(response => {
            hideLoader();
            
            if (response.data.success) {
                showToast(response.data.message || 'Operation successful', 'success');
                
                // Redirect if specified
                if (response.data.redirect) {
                    window.location.href = response.data.redirect;
                }
                
                // Reload if specified
                if (response.data.reload) {
                    window.location.reload();
                }
                
                // Reset form if specified
                if (response.data.reset) {
                    form.reset();
                }
            }
        })
        .catch(error => {
            hideLoader();
            
            if (error.response) {
                const errors = error.response.data.errors;
                
                if (errors) {
                    // Display validation errors
                    Object.keys(errors).forEach(field => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            input.classList.add('is-invalid');
                            
                            const feedback = input.nextElementSibling;
                            if (feedback && feedback.classList.contains('invalid-feedback')) {
                                feedback.textContent = errors[field][0];
                            } else {
                                input.insertAdjacentHTML('afterend', `
                                    <div class="invalid-feedback">${errors[field][0]}</div>
                                `);
                            }
                        }
                    });
                } else {
                    showToast(error.response.data.message || 'An error occurred', 'danger');
                }
            } else {
                showToast('Network error. Please check your connection.', 'danger');
            }
        });
    }
});

/**
 * Clear validation errors on input
 */
document.addEventListener('input', function(e) {
    const input = e.target;
    if (input.classList.contains('is-invalid')) {
        input.classList.remove('is-invalid');
        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.remove();
        }
    }
});

/**
 * Handle delete confirmations
 */
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('delete-btn')) {
        e.preventDefault();
        
        const url = e.target.getAttribute('data-url');
        const confirmMessage = e.target.getAttribute('data-confirm') || 'Are you sure you want to delete this item?';
        
        if (confirm(confirmMessage)) {
            showLoader();
            
            axios.delete(url)
                .then(response => {
                    hideLoader();
                    showToast(response.data.message || 'Deleted successfully', 'success');
                    
                    // Remove the row or reload
                    if (response.data.reload) {
                        window.location.reload();
                    } else {
                        const row = e.target.closest('tr');
                        if (row) {
                            row.remove();
                        }
                    }
                })
                .catch(error => {
                    hideLoader();
                    showToast(error.response?.data?.message || 'Delete failed', 'danger');
                });
        }
    }
});

/**
 * Auto-dismiss alerts
 */
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

/**
 * Initialize tooltips
 */
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

/**
 * Initialize popovers
 */
document.addEventListener('DOMContentLoaded', function() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

/**
 * Handle Axios request interceptor
 */
axios.interceptors.request.use(
    config => {
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

/**
 * Handle Axios response interceptor
 */
axios.interceptors.response.use(
    response => {
        return response;
    },
    error => {
        if (error.response && error.response.status === 401) {
            // Unauthorized - redirect to login
            localStorage.removeItem('auth_token');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

/**
 * Export utility functions for use in other scripts
 */
window.nexthospital = {
    showLoader,
    hideLoader,
    showToast,
    formatCurrency,
    formatDate,
    formatDateTime,
    debounce
};
