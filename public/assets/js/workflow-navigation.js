/**
 * Workflow Navigation System
 * Handles auto-redirects, AJAX calls, and UI updates for workflow navigation
 */

(function() {
    'use strict';

    const WorkflowNavigation = {
        /**
         * Initialize workflow navigation
         */
        init: function() {
            this.handleAutoRedirects();
            this.handleNextStepSuggestions();
            this.setupEventListeners();
        },

        /**
         * Handle auto-redirects for workflow steps
         */
        handleAutoRedirects: function() {
            // Skip auto-redirect on consultation show pages
            if (window.location.pathname.includes('/consultations/') && window.location.pathname.match(/\/consultations\/\d+$/)) {
                return; // Don't auto-redirect on consultation detail pages
            }
            
            // Check for workflow next step data in session flash
            const nextStepData = this.getNextStepFromSession();
            
            if (nextStepData && nextStepData.step && nextStepData.step.auto_redirect && nextStepData.route && nextStepData.route.url) {
                // Show a brief notification before redirecting
                this.showNotification('Redirecting to next step...', 'info');
                
                // Small delay to allow user to see the notification
                setTimeout(() => {
                    window.location.href = nextStepData.route.url;
                }, 1500);
            }
        },

        /**
         * Handle next step suggestions displayed on page
         */
        handleNextStepSuggestions: function() {
            // Check if there's a next step suggestion card on the page
            const suggestionCard = document.querySelector('.workflow-next-step-card');
            
            if (suggestionCard) {
                const workflowInstanceId = suggestionCard.dataset.workflowInstanceId;
                
                // Set up click handler for continue button
                const continueBtn = suggestionCard.querySelector('a.btn-primary');
                if (continueBtn) {
                    continueBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const url = continueBtn.getAttribute('href');
                        if (url) {
                            // Log the action before redirecting
                            this.logWorkflowAction(workflowInstanceId, 'redirected', {
                                url: url
                            });
                            
                            window.location.href = url;
                        }
                    });
                }
            }
        },

        /**
         * Setup event listeners for workflow actions
         */
        setupEventListeners: function() {
            // Listen for form submissions that might trigger workflow steps
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (form.dataset.workflowAction) {
                    this.handleWorkflowFormSubmit(form);
                }
            });

            // Listen for AJAX success responses that might contain workflow data
            if (typeof jQuery !== 'undefined') {
                $(document).ajaxSuccess((event, xhr, settings) => {
                    if (xhr.responseJSON && xhr.responseJSON.workflow) {
                        this.handleWorkflowResponse(xhr.responseJSON.workflow);
                    }
                });
            }
        },

        /**
         * Handle workflow form submission
         */
        handleWorkflowFormSubmit: function(form) {
            const workflowInstanceId = form.dataset.workflowInstanceId;
            const stepKey = form.dataset.workflowStep;
            
            if (workflowInstanceId && stepKey) {
                // The actual step completion is handled server-side
                // This is just for client-side logging
                this.logWorkflowAction(workflowInstanceId, 'submitted', {
                    step: stepKey,
                    form_id: form.id
                });
            }
        },

        /**
         * Handle workflow response from AJAX calls
         */
        handleWorkflowResponse: function(workflowData) {
            if (!workflowData || !workflowData.has_next_step) {
                return;
            }

            // Display next step suggestion
            this.displayNextStepSuggestion(workflowData);
        },

        /**
         * Display next step suggestion on the page
         */
        displayNextStepSuggestion: function(suggestion) {
            // Remove any existing suggestion
            const existing = document.querySelector('.workflow-next-step-card');
            if (existing) {
                existing.remove();
            }

            // Create new suggestion card
            const card = this.createSuggestionCard(suggestion);
            
            // Insert at the top of the main content area
            const mainContent = document.querySelector('.main-content') || document.querySelector('main') || document.body;
            if (mainContent) {
                mainContent.insertBefore(card, mainContent.firstChild);
            }

            // Handle auto-redirect if needed
            if (suggestion.step && suggestion.step.auto_redirect && suggestion.route && suggestion.route.url) {
                setTimeout(() => {
                    window.location.href = suggestion.route.url;
                }, 2000);
            }
        },

        /**
         * Create suggestion card HTML
         */
        createSuggestionCard: function(suggestion) {
            const card = document.createElement('div');
            card.className = 'alert alert-info alert-dismissible fade show workflow-next-step-card';
            card.setAttribute('role', 'alert');
            card.setAttribute('data-workflow-instance-id', suggestion.workflow_instance_id || '');

            const continueBtn = suggestion.route && suggestion.route.url && !suggestion.step.auto_redirect
                ? `<a href="${suggestion.route.url}" class="btn btn-primary btn-sm">
                    <i class="fas fa-arrow-right me-1"></i>
                    Continue
                   </a>`
                : suggestion.step.auto_redirect
                ? '<span class="badge bg-success">Auto-redirecting...</span>'
                : '';

            card.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-2">
                            <i class="fas fa-arrow-right me-2"></i>
                            Next Step: ${suggestion.step.name || 'Continue'}
                        </h5>
                        ${suggestion.step.description ? `<p class="mb-2">${suggestion.step.description}</p>` : ''}
                        ${suggestion.message ? `<p class="mb-0 text-muted small">${suggestion.message}</p>` : ''}
                    </div>
                    <div class="ms-3">
                        ${continueBtn}
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;

            // Add click handler for continue button
            const btn = card.querySelector('a.btn-primary');
            if (btn) {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.logWorkflowAction(suggestion.workflow_instance_id, 'redirected', {
                        url: btn.getAttribute('href')
                    });
                    window.location.href = btn.getAttribute('href');
                });
            }

            return card;
        },

        /**
         * Get next step data from session flash (if available)
         */
        getNextStepFromSession: function() {
            // Check if Laravel has set workflow_next_step in session
            // This would be set server-side via ->with('workflow_next_step', $suggestion)
            // For now, we'll check the page for any data attributes or script tags
            const scriptTag = document.querySelector('script[data-workflow-next-step]');
            if (scriptTag) {
                try {
                    return JSON.parse(scriptTag.dataset.workflowNextStep);
                } catch (e) {
                    console.error('Error parsing workflow next step data:', e);
                }
            }
            return null;
        },

        /**
         * Log workflow action (client-side)
         */
        logWorkflowAction: function(workflowInstanceId, actionType, metadata) {
            // This could send an AJAX request to log the action
            // For now, we'll just log to console
            console.log('Workflow action:', {
                workflow_instance_id: workflowInstanceId,
                action_type: actionType,
                metadata: metadata
            });

            // Optionally send to server
            if (typeof fetch !== 'undefined' && workflowInstanceId) {
                fetch('/api/workflow/log-action', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        workflow_instance_id: workflowInstanceId,
                        action_type: actionType,
                        metadata: metadata
                    })
                }).catch(err => console.error('Error logging workflow action:', err));
            }
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            // Use Bootstrap toast or alert if available
            if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
                // Create and show toast notification
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white bg-${type} border-0`;
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
                toast.addEventListener('hidden.bs.toast', () => toast.remove());
            } else {
                // Fallback to console
                console.log(`[${type.toUpperCase()}] ${message}`);
            }
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => WorkflowNavigation.init());
    } else {
        WorkflowNavigation.init();
    }

    // Make available globally
    window.WorkflowNavigation = WorkflowNavigation;
})();

