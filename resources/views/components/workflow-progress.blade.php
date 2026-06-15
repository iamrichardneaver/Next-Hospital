@props(['instance', 'showLabels' => true, 'clickable' => true])

@php
    if (!$instance) {
        return;
    }
    
    $workflow = $instance->workflow;
    if (!$workflow) {
        return;
    }
    
    $steps = $workflow->steps()->orderBy('order')->get();
    $currentStep = $instance->currentStep;
    $currentStepOrder = $currentStep ? $currentStep->order : 0;
    $totalSteps = $steps->count();
    
    // Get completed steps from action logs
    $completedStepIds = $instance->actionLogs()
        ->where('action_type', 'completed')
        ->pluck('step_id')
        ->toArray();
    
    // Calculate progress based on completed steps
    // Count completed steps (steps that have been marked as completed)
    $completedStepsCount = count($completedStepIds);
    
    // If current step exists and is not completed, give it partial credit (50%)
    // This provides visual feedback that the current step is in progress
    $progressPercentage = 0;
    if ($totalSteps > 0) {
        $baseProgress = ($completedStepsCount / $totalSteps) * 100;
        
        // Add partial credit for current step if it's not completed
        if ($currentStep && !in_array($currentStep->id, $completedStepIds)) {
            $stepProgress = (1 / $totalSteps) * 50; // 50% of one step
            $progressPercentage = round($baseProgress + $stepProgress);
        } else {
            $progressPercentage = round($baseProgress);
        }
        
        // Ensure percentage doesn't exceed 100%
        $progressPercentage = min(100, $progressPercentage);
    }
@endphp

@if($instance && $workflow && $steps->count() > 0)
<div class="workflow-progress-container mb-4" id="workflow-progress-{{ $instance->id }}" data-workflow-instance-id="{{ $instance->id }}">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">
            <i class="fas fa-tasks me-2"></i>
            {{ $workflow->name }} Progress
        </h6>
        <span class="badge bg-primary" id="progress-percentage-{{ $instance->id }}">{{ $progressPercentage }}% Complete</span>
    </div>
    
    <!-- Progress Bar -->
    <div class="progress mb-3" style="height: 8px;">
        <div class="progress-bar bg-success" id="progress-bar-{{ $instance->id }}" role="progressbar" 
             style="width: {{ $progressPercentage }}%" 
             aria-valuenow="{{ $progressPercentage }}" 
             aria-valuemin="0" 
             aria-valuemax="100">
        </div>
    </div>
    
    <!-- Steps Timeline -->
    <div class="workflow-steps-timeline">
        <div class="row g-2">
            @foreach($steps as $step)
                @php
                    $isCompleted = in_array($step->id, $completedStepIds);
                    $isCurrent = $currentStep && $step->id === $currentStep->id;
                    $isUpcoming = $step->order > $currentStepOrder;
                    $stepClass = '';
                    $iconClass = '';
                    
                    if ($isCompleted) {
                        $stepClass = 'completed';
                        $iconClass = 'fas fa-check-circle text-success';
                    } elseif ($isCurrent) {
                        $stepClass = 'current';
                        $iconClass = 'fas fa-circle-notch fa-spin text-primary';
                    } else {
                        $stepClass = 'upcoming';
                        $iconClass = 'far fa-circle text-muted';
                    }
                @endphp
                
                <div class="col-12 col-md-6 col-lg-3 mb-2">
                    <div class="workflow-step-card {{ $stepClass }} p-3 rounded border 
                        {{ $isCurrent ? 'border-primary shadow-sm' : ($isCompleted ? 'border-success' : 'border-secondary') }}"
                        @if($clickable && !$isUpcoming && $step->route_name)
                            @php
                                // Build route parameters - try to use route_parameters from step if available
                                $routeParams = ['id' => $instance->entity_id];
                                if ($step->route_parameters) {
                                    foreach ($step->route_parameters as $key => $value) {
                                        if ($value === 'entity_id') {
                                            $routeParams[$key] = $instance->entity_id;
                                        } elseif ($value === 'workflow_instance_id') {
                                            $routeParams[$key] = $instance->id;
                                        } else {
                                            $routeParams[$key] = $value;
                                        }
                                    }
                                }
                            @endphp
                            onclick="window.location.href='{{ route($step->route_name, $routeParams) }}'"
                            style="cursor: pointer;"
                        @endif>
                        <div class="d-flex align-items-center">
                            <div class="step-icon me-3">
                                <i class="{{ $iconClass }} fa-2x"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="step-number small text-muted mb-1">
                                    Step {{ $step->order }} of {{ $totalSteps }}
                                </div>
                                <div class="step-name fw-bold {{ $isCurrent ? 'text-primary' : '' }}">
                                    {{ $step->step_name }}
                                </div>
                                @if($showLabels && $step->step_description)
                                    <div class="step-description small text-muted mt-1">
                                        {{ Str::limit($step->step_description, 50) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    
    <!-- Current Step Info -->
    @if($currentStep)
    <div class="alert alert-info mt-3 mb-0">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle me-2"></i>
            <div>
                <strong>Current Step:</strong> {{ $currentStep->step_name }}
                @if($currentStep->step_description)
                    <br><small>{{ $currentStep->step_description }}</small>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

<style>
.workflow-progress-container {
    background: #ffffff;
    padding: 1.5rem;
    border-radius: 0.5rem;
    border: 1px solid #dee2e6;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.workflow-step-card {
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.workflow-step-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.workflow-step-card.completed {
    background: #d1e7dd;
    border-color: #198754 !important;
}

.workflow-step-card.current {
    background: #cfe2ff;
    border-color: #0d6efd !important;
    animation: pulse 2s infinite;
}

.workflow-step-card.upcoming {
    opacity: 0.6;
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4);
    }
    50% {
        box-shadow: 0 0 0 8px rgba(13, 110, 253, 0);
    }
}
</style>
@endif

