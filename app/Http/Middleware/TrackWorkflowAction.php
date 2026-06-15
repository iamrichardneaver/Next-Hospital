<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\Log;

class TrackWorkflowAction
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track successful responses (2xx status codes)
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return $response;
        }

        // Check if this is a workflow-related action
        // This can be determined by route name or controller action
        $route = $request->route();
        if (!$route) {
            return $response;
        }

        $routeName = $route->getName();
        $action = $route->getActionMethod();

        // List of workflow-related routes/actions
        $workflowActions = [
            'visits.store',
            'vitals.store',
            'consultations.store',
            'lab-requests.store',
            'prescriptions.store',
            'pharmacy.dispense',
            'billing.store',
        ];

        // Check if this is a workflow action
        $isWorkflowAction = false;
        foreach ($workflowActions as $workflowRoute) {
            if (str_contains($routeName, $workflowRoute) || str_contains($action, $workflowRoute)) {
                $isWorkflowAction = true;
                break;
            }
        }

        if (!$isWorkflowAction) {
            return $response;
        }

        // Try to extract entity from response or request
        // This is a simplified version - can be enhanced
        try {
            // Get entity ID from route parameters
            $entityId = $route->parameter('id') 
                ?? $route->parameter('visit') 
                ?? $route->parameter('consultation')
                ?? $route->parameter('labRequest')
                ?? $route->parameter('prescription');

            if ($entityId) {
                // Determine entity type from route
                $entityType = $this->determineEntityType($routeName, $action);

                if ($entityType) {
                    $instance = $this->workflowService->getInstanceForEntity($entityType, $entityId);

                    if ($instance) {
                        // Log the action (action type determined by route/action)
                        $actionType = $this->determineActionType($routeName, $action);
                        $this->workflowService->logAction(
                            $instance->id,
                            $instance->current_step_id,
                            $actionType,
                            auth()->id() ?? 0,
                            ['route' => $routeName, 'action' => $action]
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail - don't break the request
            Log::debug("Error tracking workflow action: " . $e->getMessage());
        }

        return $response;
    }

    /**
     * Determine entity type from route name or action.
     */
    protected function determineEntityType(string $routeName, string $action): ?string
    {
        if (str_contains($routeName, 'visit') || str_contains($action, 'visit')) {
            return 'visit';
        }

        if (str_contains($routeName, 'consultation') || str_contains($action, 'consultation')) {
            return 'consultation';
        }

        if (str_contains($routeName, 'lab') || str_contains($action, 'lab')) {
            return 'lab_request';
        }

        if (str_contains($routeName, 'prescription') || str_contains($action, 'prescription')) {
            return 'prescription';
        }

        return null;
    }

    /**
     * Determine action type from route name or action.
     */
    protected function determineActionType(string $routeName, string $action): string
    {
        if (str_contains($action, 'store') || str_contains($action, 'create')) {
            return 'completed';
        }

        if (str_contains($action, 'update') || str_contains($action, 'edit')) {
            return 'completed';
        }

        return 'completed'; // Default
    }
}

