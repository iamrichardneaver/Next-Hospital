<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\WorkflowService;
use App\Services\WorkflowNavigationService;
use App\Models\WorkflowInstance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WorkflowController extends Controller
{
    protected WorkflowService $workflowService;
    protected WorkflowNavigationService $navigationService;

    public function __construct(WorkflowService $workflowService, WorkflowNavigationService $navigationService)
    {
        $this->workflowService = $workflowService;
        $this->navigationService = $navigationService;
    }

    /**
     * Get next step suggestion for a workflow instance.
     */
    public function getNextStep(Request $request, int $workflowInstanceId): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $suggestion = $this->navigationService->getNextStepSuggestion($workflowInstanceId, $user->id);

        return response()->json([
            'success' => true,
            'workflow' => $this->navigationService->formatSuggestionForResponse($suggestion),
        ]);
    }

    /**
     * Complete a workflow step and get next step.
     */
    public function completeStep(Request $request, int $workflowInstanceId): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'step_key' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        $instance = WorkflowInstance::find($workflowInstanceId);
        if (!$instance) {
            return response()->json(['error' => 'Workflow instance not found'], 404);
        }

        // Find step by key
        $step = $instance->workflow->steps()->where('step_key', $request->step_key)->first();
        if (!$step) {
            return response()->json(['error' => 'Step not found'], 404);
        }

        // Complete the step
        $nextStep = $this->workflowService->completeStep(
            $workflowInstanceId,
            $step->id,
            $user->id,
            $request->metadata ?? []
        );

        // Get next step suggestion
        $suggestion = $this->navigationService->getNextStepSuggestion($workflowInstanceId, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Step completed successfully',
            'workflow' => $this->navigationService->formatSuggestionForResponse($suggestion),
        ]);
    }

    /**
     * Get workflow instance status.
     */
    public function getStatus(Request $request, int $workflowInstanceId): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $workflowService = app(WorkflowService::class);
        $progress = $workflowService->getWorkflowProgress($workflowInstanceId);
        
        if (!$progress) {
            return response()->json(['success' => false, 'message' => 'Workflow instance not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }

    /**
     * Get workflow progress for display.
     */
    public function getProgress(Request $request, int $workflowInstanceId): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $workflowService = app(WorkflowService::class);
        $progress = $workflowService->getWorkflowProgress($workflowInstanceId);
        
        if (!$progress) {
            return response()->json(['success' => false, 'message' => 'Workflow instance not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }

    /**
     * Log a workflow action (client-side logging).
     */
    public function logAction(Request $request): JsonResponse
    {
        $request->validate([
            'workflow_instance_id' => 'required|exists:workflow_instances,id',
            'action_type' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        $this->navigationService->logAction(
            $request->workflow_instance_id,
            $request->action_type,
            $request->metadata ?? []
        );

        return response()->json(['success' => true]);
    }
}

