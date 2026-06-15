@props(['suggestion', 'pendingItems' => null])

@if($suggestion && isset($suggestion['has_next_step']) && $suggestion['has_next_step'])
    @php
        $pendingCount = 0;
        $pendingDetails = [];
        
        // Get pending items from prop or from suggestion array
        $items = $pendingItems ?? ($suggestion['pending_items'] ?? null);
        
        if ($items) {
            if (isset($items['prescriptions'])) {
                $prescriptionCount = count($items['prescriptions']);
                $pendingCount += $prescriptionCount;
                $drugCount = 0;
                foreach ($items['prescriptions'] as $prescription) {
                    if (isset($prescription['drug_names'])) {
                        $drugCount += count($prescription['drug_names']);
                    }
                }
                if ($drugCount > 0) {
                    $pendingDetails[] = $prescriptionCount . ' prescription(s) with ' . $drugCount . ' medication(s)';
                } else {
                    $pendingDetails[] = $prescriptionCount . ' prescription(s)';
                }
            }
            if (isset($items['lab_tests'])) {
                $pendingCount += count($items['lab_tests']);
                $pendingDetails[] = count($items['lab_tests']) . ' lab test(s)';
            }
            if (isset($items['radiology'])) {
                $pendingCount += count($items['radiology']);
                $pendingDetails[] = count($items['radiology']) . ' imaging test(s)';
            }
        }
    @endphp
    
    <div class="workflow-next-step-card sticky-top mb-4" style="top: 80px; z-index: 1000;" data-workflow-instance-id="{{ $suggestion['workflow_instance_id'] ?? '' }}">
        <div class="card border-primary shadow-lg">
            <div class="card-body p-4">
                <div class="d-flex align-items-start">
                    <div class="flex-grow-1">
                        <!-- Success Icon & Message -->
                        <div class="d-flex align-items-center mb-3">
                            <div class="success-icon me-3">
                                <i class="fas fa-check-circle text-success fa-3x"></i>
                            </div>
                            <div>
                                <h4 class="mb-1 text-success">
                                    <i class="fas fa-check me-2"></i>
                                    Action Completed Successfully!
                                </h4>
                                <p class="text-muted mb-0">Ready to proceed to the next step</p>
                            </div>
                        </div>
                        
                        <!-- Next Step Info -->
                        <div class="next-step-info bg-light p-3 rounded mb-3">
                            <h5 class="mb-2">
                                <i class="fas fa-arrow-right text-primary me-2"></i>
                                Next Step: {{ $suggestion['step']['name'] ?? 'Continue' }}
                            </h5>
                            @if(isset($suggestion['step']['description']))
                                <p class="mb-2">{{ $suggestion['step']['description'] }}</p>
                            @endif
                            
                            <!-- Pending Items Info -->
                            @if($pendingCount > 0)
                                <div class="alert alert-warning mb-0 mt-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Pending Items:</strong>
                                    <ul class="mb-0 mt-1">
                                        @foreach($pendingDetails as $detail)
                                            <li>{{ $detail }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 flex-wrap">
                            @if(isset($suggestion['route']['url']) && !($suggestion['step']['auto_redirect'] ?? false))
                                <a href="{{ $suggestion['route']['url'] }}" class="btn btn-primary btn-lg">
                                    <i class="fas fa-arrow-right me-2"></i>
                                    Continue to {{ $suggestion['step']['name'] }}
                                </a>
                            @elseif(isset($suggestion['route']['url']))
                                <button class="btn btn-success btn-lg" id="autoRedirectBtn" disabled>
                                    <i class="fas fa-spinner fa-spin me-2"></i>
                                    Auto-redirecting...
                                </button>
                                <script>
                                (function() {
                                    // Auto-redirect after a short delay
                                    const redirectUrl = "{{ $suggestion['route']['url'] }}";
                                    const redirectDelay = {{ $suggestion['step']['redirect_delay'] ?? 2000 }}; // Default 2 seconds
                                    
                                    setTimeout(function() {
                                        if (redirectUrl) {
                                            window.location.href = redirectUrl;
                                        }
                                    }, redirectDelay);
                                })();
                                </script>
                            @endif
                            
                            <button type="button" class="btn btn-outline-secondary" onclick="this.closest('.workflow-next-step-card').remove()">
                                <i class="fas fa-times me-2"></i>
                                Dismiss
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<style>
.workflow-next-step-card {
    animation: slideDown 0.5s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.workflow-next-step-card .card {
    border-width: 2px;
}

.workflow-next-step-card .success-icon {
    animation: bounceIn 0.6s ease-out;
}

@keyframes bounceIn {
    0% {
        transform: scale(0);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

@media (max-width: 768px) {
    .workflow-next-step-card {
        position: relative !important;
        top: 0 !important;
    }
}
</style>

