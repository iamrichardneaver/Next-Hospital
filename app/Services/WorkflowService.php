<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Models\WorkflowTransition;
use App\Models\WorkflowActionLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowService
{
    /**
     * Initialize a workflow instance for an entity.
     */
    public function initializeWorkflow(string $workflowName, string $entityType, int $entityId, int $userId): ?WorkflowInstance
    {
        try {
            $workflow = Workflow::where('name', $workflowName)
                ->where('is_active', true)
                ->first();

            if (!$workflow) {
                Log::warning("Workflow '{$workflowName}' not found or inactive");
                return null;
            }

            // Get the first step
            $firstStep = $workflow->steps()->orderBy('order')->first();

            if (!$firstStep) {
                Log::warning("No steps found for workflow '{$workflowName}'");
                return null;
            }

            // Create workflow instance
            $instance = WorkflowInstance::create([
                'workflow_id' => $workflow->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'current_step_id' => $firstStep->id,
                'status' => 'active',
                'started_by' => $userId,
                'started_at' => now(),
                'metadata' => [],
            ]);

            // Log the start action
            $this->logAction($instance->id, $firstStep->id, 'started', $userId, []);

            return $instance;
        } catch (\Exception $e) {
            Log::error("Error initializing workflow: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get next available steps for a workflow instance based on user permissions.
     */
    public function getNextSteps(int $workflowInstanceId, int $userId): array
    {
        $instance = WorkflowInstance::with(['currentStep', 'workflow.steps', 'workflow.transitions'])->find($workflowInstanceId);

        if (!$instance || !$instance->isActive()) {
            return [];
        }

        $user = User::find($userId);
        if (!$user) {
            return [];
        }

        $currentStep = $instance->currentStep;
        if (!$currentStep) {
            return [];
        }

        // Get all transitions from current step
        $transitions = WorkflowTransition::where('workflow_id', $instance->workflow_id)
            ->where('from_step_id', $currentStep->id)
            ->orderBy('priority', 'desc')
            ->get();

        $nextSteps = [];

        foreach ($transitions as $transition) {
            $toStep = $transition->toStep;

            if (!$toStep) {
                continue;
            }

            // Check permission
            if ($transition->required_permission && !$user->can($transition->required_permission)) {
                continue;
            }

            // Check step permission
            if ($toStep->required_permission && !$user->can($toStep->required_permission)) {
                continue;
            }

            // Check condition logic
            if (!$transition->isAllowed($instance->metadata ?? [])) {
                continue;
            }

            $nextSteps[] = [
                'step' => $toStep,
                'transition' => $transition,
            ];
        }

        return $nextSteps;
    }

    /**
     * Complete a workflow step and move to next step.
     * Allows completing a step by key even when earlier steps were skipped in the UI
     * (e.g. vitals/consultation actions while current_step is still patient_registration).
     */
    public function completeStep(int $workflowInstanceId, int $stepId, int $userId, array $metadata = []): ?WorkflowStep
    {
        $instance = WorkflowInstance::with(['currentStep', 'workflow.steps'])->find($workflowInstanceId);

        if (!$instance || !$instance->isActive()) {
            return null;
        }

        $step = WorkflowStep::find($stepId);
        if (!$step || $step->workflow_id != $instance->workflow_id) {
            return null;
        }

        $currentStep = $instance->currentStep;
        if (!$currentStep) {
            return null;
        }

        if (!empty($metadata)) {
            $currentMetadata = $instance->metadata ?? [];
            $instance->update(['metadata' => array_merge($currentMetadata, $metadata)]);
        }

        // Auto-complete intermediate steps when completing a later step out of order
        if ($step->order > $currentStep->order) {
            $intermediateSteps = $instance->workflow->steps()
                ->where('order', '>=', $currentStep->order)
                ->where('order', '<', $step->order)
                ->orderBy('order')
                ->get();

            foreach ($intermediateSteps as $intermediateStep) {
                $this->logAction($workflowInstanceId, $intermediateStep->id, 'completed', $userId, [
                    'auto_skipped' => true,
                ]);
            }
        } elseif ($step->order < $currentStep->order) {
            $this->logAction($workflowInstanceId, $stepId, 'completed', $userId, array_merge($metadata, [
                'retroactive' => true,
            ]));

            return $this->finalizeIfComplete($instance->fresh(), $userId);
        }

        $this->logAction($workflowInstanceId, $stepId, 'completed', $userId, $metadata);

        $nextSteps = $this->getNextSteps($workflowInstanceId, $userId);

        if (empty($nextSteps)) {
            return $this->finalizeIfComplete($instance->fresh(), $userId);
        }

        $nextStepData = $nextSteps[0];
        $nextStep = $nextStepData['step'];

        $instance->update(['current_step_id' => $nextStep->id]);

        $this->logAction($workflowInstanceId, $nextStep->id, 'redirected', $userId, [
            'from_step_id' => $stepId,
            'to_step_id' => $nextStep->id,
        ]);

        return $nextStep;
    }

    /**
     * Complete a workflow step by step key.
     */
    public function completeStepByKey(int $workflowInstanceId, string $stepKey, int $userId, array $metadata = []): ?WorkflowStep
    {
        $instance = WorkflowInstance::with('workflow')->find($workflowInstanceId);
        if (!$instance) {
            return null;
        }

        $step = $instance->workflow->steps()->where('step_key', $stepKey)->first();
        if (!$step) {
            return null;
        }

        return $this->completeStep($workflowInstanceId, $step->id, $userId, $metadata);
    }

    /**
     * Force-complete an active workflow instance for an entity (visit closed, cancelled, etc.).
     */
    public function completeWorkflowForEntity(string $entityType, int $entityId, int $userId, string $reason = 'entity_completed'): bool
    {
        $instance = $this->getInstanceForEntity($entityType, $entityId);
        if (!$instance || !$instance->isActive()) {
            return false;
        }

        if ($instance->current_step_id) {
            $this->logAction($instance->id, $instance->current_step_id, 'completed', $userId, [
                'force_complete' => true,
                'reason' => $reason,
            ]);
        }

        $instance->markAsCompleted();

        return true;
    }

    /**
     * Mark workflow completed when all required steps are done or at terminal step.
     */
    protected function finalizeIfComplete(WorkflowInstance $instance, int $userId): ?WorkflowStep
    {
        $instance->load(['currentStep', 'workflow.steps']);

        if ($this->isWorkflowComplete($instance) || $instance->currentStep?->step_key === 'visit_closure') {
            $instance->markAsCompleted();
            return null;
        }

        return null;
    }

    /**
     * Get current step for a workflow instance.
     */
    public function getCurrentStep(int $workflowInstanceId): ?WorkflowStep
    {
        $instance = WorkflowInstance::with('currentStep')->find($workflowInstanceId);

        return $instance?->currentStep;
    }

    /**
     * Get workflow progress data for display.
     */
    public function getWorkflowProgress(int $workflowInstanceId): ?array
    {
        $instance = WorkflowInstance::with(['workflow.steps', 'currentStep', 'actionLogs'])->find($workflowInstanceId);
        
        if (!$instance) {
            return null;
        }

        $workflow = $instance->workflow;
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
        $completedStepsCount = count($completedStepIds);
        
        // If current step exists and is not completed, give it partial credit (50%)
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
        
        $stepsData = [];
        foreach ($steps as $step) {
            $isCompleted = in_array($step->id, $completedStepIds);
            $isCurrent = $currentStep && $step->id === $currentStep->id;
            $isUpcoming = $step->order > $currentStepOrder;
            
            $stepsData[] = [
                'id' => $step->id,
                'key' => $step->step_key,
                'name' => $step->step_name,
                'description' => $step->step_description,
                'order' => $step->order,
                'route_name' => $step->route_name,
                'is_completed' => $isCompleted,
                'is_current' => $isCurrent,
                'is_upcoming' => $isUpcoming,
            ];
        }
        
        return [
            'workflow_name' => $workflow->name,
            'workflow_id' => $workflow->id,
            'instance_id' => $instance->id,
            'current_step' => $currentStep ? [
                'id' => $currentStep->id,
                'name' => $currentStep->step_name,
                'description' => $currentStep->step_description,
                'order' => $currentStep->order,
            ] : null,
            'total_steps' => $totalSteps,
            'current_step_order' => $currentStepOrder,
            'progress_percentage' => $progressPercentage,
            'steps' => $stepsData,
            'completed_steps_count' => count($completedStepIds),
        ];
    }

    /**
     * Check if user can access a specific step.
     */
    public function canAccessStep(int $userId, int $stepId): bool
    {
        $user = User::find($userId);
        $step = WorkflowStep::find($stepId);

        if (!$user || !$step) {
            return false;
        }

        return $step->canAccess($user);
    }

    /**
     * Get navigation suggestion for next step.
     */
    public function getNavigationSuggestion(int $workflowInstanceId, int $userId): ?array
    {
        $nextSteps = $this->getNextSteps($workflowInstanceId, $userId);

        if (empty($nextSteps)) {
            return null;
        }

        $nextStepData = $nextSteps[0];
        $nextStep = $nextStepData['step'];
        $instance = WorkflowInstance::find($workflowInstanceId);

        if (!$instance) {
            return null;
        }

        // Build route parameters
        $routeParams = $this->buildRouteParameters($nextStep, $instance);

        return [
            'step' => [
                'id' => $nextStep->id,
                'key' => $nextStep->step_key,
                'name' => $nextStep->step_name,
                'description' => $nextStep->step_description,
                'auto_redirect' => $nextStep->auto_redirect,
            ],
            'route' => [
                'name' => $nextStep->route_name,
                'parameters' => $routeParams,
            ],
        ];
    }

    /**
     * Build route parameters from step configuration and instance metadata.
     */
    protected function buildRouteParameters(WorkflowStep $step, WorkflowInstance $instance): array
    {
        $params = [];

        if ($step->route_parameters) {
            foreach ($step->route_parameters as $key => $value) {
                // Support dynamic values like 'entity_id', 'patient_id', etc.
                if ($value === 'entity_id') {
                    $params[$key] = $instance->entity_id;
                } elseif ($value === 'workflow_instance_id') {
                    $params[$key] = $instance->id;
                } elseif (isset($instance->metadata[$value])) {
                    $params[$key] = $instance->metadata[$value];
                } else {
                    $params[$key] = $value;
                }
            }
        } else {
            // Default: use entity_id
            $params['id'] = $instance->entity_id;
        }

        return $params;
    }

    /**
     * Check if workflow is complete (all required steps done).
     */
    protected function isWorkflowComplete(WorkflowInstance $instance): bool
    {
        // Simple check: if no next steps and current step is the last required step
        // This can be enhanced with more sophisticated logic
        $workflow = $instance->workflow;
        $requiredSteps = $workflow->steps()->where('is_required', true)->get();

        // Check if all required steps have been completed
        $completedSteps = $instance->actionLogs()
            ->where('action_type', 'completed')
            ->pluck('step_id')
            ->toArray();

        foreach ($requiredSteps as $requiredStep) {
            if (!in_array($requiredStep->id, $completedSteps)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Log a workflow action.
     */
    public function logAction(int $workflowInstanceId, ?int $stepId, string $actionType, int $userId, array $metadata = []): void
    {
        WorkflowActionLog::create([
            'workflow_instance_id' => $workflowInstanceId,
            'step_id' => $stepId,
            'action_type' => $actionType,
            'user_id' => $userId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    /**
     * Get workflow instance for an entity.
     */
    public function getInstanceForEntity(string $entityType, int $entityId): ?WorkflowInstance
    {
        // Handle both 'visit' and 'App\Models\Visit' formats for compatibility
        $entityTypes = [$entityType];
        if ($entityType === 'visit') {
            $entityTypes[] = 'App\\Models\\Visit';
        } elseif ($entityType === 'App\\Models\\Visit') {
            $entityTypes[] = 'visit';
        }
        
        return WorkflowInstance::whereIn('entity_type', $entityTypes)
            ->where('entity_id', $entityId)
            ->where('status', 'active')
            ->first();
    }
}

