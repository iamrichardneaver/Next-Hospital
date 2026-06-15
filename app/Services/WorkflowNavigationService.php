<?php

namespace App\Services;

use App\Models\WorkflowStep;
use App\Models\WorkflowInstance;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

class WorkflowNavigationService
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Determine if a step should auto-redirect.
     */
    public function shouldAutoRedirect(WorkflowStep $step, User $user): bool
    {
        // Check if step has auto_redirect enabled
        if ($step->auto_redirect) {
            return true;
        }

        // Check global config (can be added later)
        // For now, return false for manual steps
        return false;
    }

    /**
     * Generate redirect route for next step.
     */
    public function getRedirectRoute(WorkflowStep $step, WorkflowInstance $instance): ?string
    {
        if (!$step->route_name) {
            return null;
        }

        try {
            // Build route parameters
            $params = $this->buildRouteParameters($step, $instance);

            // Check if route exists
            if (!Route::has($step->route_name)) {
                Log::warning("Route '{$step->route_name}' not found for workflow step");
                return null;
            }

            // Generate URL
            return route($step->route_name, $params);
        } catch (\Exception $e) {
            Log::error("Error generating redirect route: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get current step suggestion formatted for UI (after a step has been completed).
     */
    public function getCurrentStepSuggestion(int $workflowInstanceId, int $userId): ?array
    {
        $instance = WorkflowInstance::with(['currentStep', 'workflow.steps'])->find($workflowInstanceId);
        $user = User::find($userId);

        if (!$instance || !$user || !$instance->isActive()) {
            return null;
        }

        $currentStep = $instance->currentStep;
        if (!$currentStep) {
            return null;
        }

        $redirectUrl = $this->getRedirectRoute($currentStep, $instance);
        $autoRedirect = $this->shouldAutoRedirect($currentStep, $user);
        $pendingItems = $this->getPendingItemsForStep($instance, $currentStep);

        return [
            'step' => [
                'id' => $currentStep->id,
                'key' => $currentStep->step_key,
                'name' => $currentStep->step_name,
                'description' => $currentStep->step_description,
                'auto_redirect' => $autoRedirect,
            ],
            'route' => [
                'name' => $currentStep->route_name,
                'url' => $redirectUrl,
                'parameters' => ['id' => $instance->entity_id],
            ],
            'workflow_instance_id' => $workflowInstanceId,
            'pending_items' => $pendingItems,
        ];
    }

    /**
     * Get next step suggestion formatted for UI.
     */
    public function getNextStepSuggestion(int $workflowInstanceId, int $userId): ?array
    {
        $suggestion = $this->workflowService->getNavigationSuggestion($workflowInstanceId, $userId);

        if (!$suggestion) {
            return null;
        }

        $instance = WorkflowInstance::find($workflowInstanceId);
        $user = User::find($userId);
        $step = WorkflowStep::find($suggestion['step']['id']);

        if (!$instance || !$user || !$step) {
            return null;
        }

        $redirectUrl = $this->getRedirectRoute($step, $instance);
        $autoRedirect = $this->shouldAutoRedirect($step, $user);

        // Get pending items for the next step
        $pendingItems = $this->getPendingItemsForStep($instance, $step);

        return [
            'step' => [
                'id' => $step->id,
                'key' => $step->step_key,
                'name' => $step->step_name,
                'description' => $step->step_description,
                'auto_redirect' => $autoRedirect,
            ],
            'route' => [
                'name' => $step->route_name,
                'url' => $redirectUrl,
                'parameters' => $suggestion['route']['parameters'],
            ],
            'workflow_instance_id' => $workflowInstanceId,
            'pending_items' => $pendingItems,
        ];
    }

    /**
     * Get pending items for a workflow step (prescriptions, lab tests, etc.).
     */
    protected function getPendingItemsForStep(WorkflowInstance $instance, WorkflowStep $step): ?array
    {
        $pendingItems = [];

        // Check if entity is a Visit (support both 'visit' and 'App\Models\Visit' formats)
        $isVisit = in_array($instance->entity_type, ['visit', 'App\\Models\\Visit', 'App\Models\Visit']);
        
        if (!$isVisit) {
            return !empty($pendingItems) ? $pendingItems : null;
        }

        $visit = \App\Models\Visit::find($instance->entity_id);
        if (!$visit) {
            return !empty($pendingItems) ? $pendingItems : null;
        }

        // If next step is pharmacy, check for pending prescriptions
        if ($step->step_key === 'pharmacy_dispensing') {
            // Get the most recent consultation for this visit
            $consultation = $visit->consultations()->latest('id')->first();
            
            if ($consultation) {
                $prescriptions = \App\Models\Prescription::where('consultation_id', $consultation->id)
                    ->where('status', 'pending')
                    ->with('orders.drug') // Load drug orders and drugs
                    ->get();
                    
                if ($prescriptions->count() > 0) {
                    $pendingItems['prescriptions'] = [];
                    foreach ($prescriptions as $prescription) {
                        // Get drug names from prescription orders
                        $drugNames = $prescription->orders->map(function ($order) {
                            return $order->drug->name ?? 'Unknown';
                        })->toArray();
                        
                        $pendingItems['prescriptions'][] = [
                            'id' => $prescription->id,
                            'drug_names' => $drugNames,
                            'prescription_number' => $prescription->prescription_number ?? $prescription->id,
                        ];
                    }
                }
            }
        }

        // If next step is lab, check for pending lab tests
        if ($step->step_key === 'laboratory_testing' && $visit->consultation) {
            $labRequests = \App\Models\LabRequest::where('consultation_id', $visit->consultation->id)
                ->where('status', 'pending')
                ->get();
            if ($labRequests->count() > 0) {
                $pendingItems['lab_tests'] = $labRequests->map(function ($lab) {
                    return [
                        'id' => $lab->id,
                        'test_name' => $lab->template->template_name ?? 'Unknown Test',
                        'status' => $lab->status,
                    ];
                })->toArray();
            }
        }

        // If next step is radiology, check for pending imaging
        if ($step->step_key === 'radiology_imaging' && $visit->consultation) {
            $radiologyRequests = \App\Models\RadiologyRequest::where('consultation_id', $visit->consultation->id)
                ->whereIn('status', ['pending', 'requested', 'scheduled'])
                ->get();
            if ($radiologyRequests->count() > 0) {
                $pendingItems['radiology'] = $radiologyRequests->map(function ($rad) {
                    return [
                        'id' => $rad->id,
                        'request_number' => $rad->request_number,
                        'modality' => $rad->modality->name ?? 'Unknown',
                        'status' => $rad->status,
                    ];
                })->toArray();
            }
        }

        return !empty($pendingItems) ? $pendingItems : null;
    }

    /**
     * Build route parameters from step configuration and instance metadata.
     */
    protected function buildRouteParameters(WorkflowStep $step, WorkflowInstance $instance): array
    {
        $params = [];

        if ($step->route_parameters) {
            foreach ($step->route_parameters as $key => $value) {
                // Support dynamic values
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
     * Log a workflow action.
     */
    public function logAction(int $workflowInstanceId, string $actionType, array $metadata = []): void
    {
        $userId = auth()->id();
        if (!$userId) {
            return;
        }

        $instance = WorkflowInstance::find($workflowInstanceId);
        $stepId = $instance?->current_step_id;

        $this->workflowService->logAction($workflowInstanceId, $stepId, $actionType, $userId, $metadata);
    }

    /**
     * Format suggestion for JSON response.
     */
    public function formatSuggestionForResponse(?array $suggestion): array
    {
        if (!$suggestion) {
            return [
                'has_next_step' => false,
                'message' => 'No next step available',
            ];
        }

        // Ensure route URL is included if not already present
        $route = $suggestion['route'] ?? [];
        if (!isset($route['url']) && isset($suggestion['step']['id'])) {
            $step = WorkflowStep::find($suggestion['step']['id']);
            $instance = WorkflowInstance::find($suggestion['workflow_instance_id'] ?? null);
            if ($step && $instance) {
                $route['url'] = $this->getRedirectRoute($step, $instance);
            }
        }

        return [
            'has_next_step' => true,
            'step' => $suggestion['step'],
            'route' => $route,
            'workflow_instance_id' => $suggestion['workflow_instance_id'],
            'pending_items' => $suggestion['pending_items'] ?? null,
            'message' => "Next step: {$suggestion['step']['name']}",
        ];
    }
}

