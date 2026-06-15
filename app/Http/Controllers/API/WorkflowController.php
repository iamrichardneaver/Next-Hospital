<?php

namespace App\Http\Controllers\API;

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
     * Get next step suggestion for a workflow instance (Mobile API).
     */
    public function getNextStep(Request $request, int $workflowInstanceId): JsonResponse
    {
        $user = auth()->guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $instance = WorkflowInstance::find($workflowInstanceId);
        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'Workflow instance not found'
            ], 404);
        }

        $suggestion = $this->navigationService->getNextStepSuggestion($workflowInstanceId, $user->id);

        return response()->json([
            'success' => true,
            'data' => $this->navigationService->formatSuggestionForResponse($suggestion),
            'message' => 'Next step retrieved successfully'
        ]);
    }

    /**
     * Complete a workflow step and get next step (Mobile API).
     */
    public function completeStep(Request $request, int $workflowInstanceId): JsonResponse
    {
        $user = auth()->guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $validator = \Validator::make($request->all(), [
            'step_key' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $instance = WorkflowInstance::find($workflowInstanceId);
        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'Workflow instance not found'
            ], 404);
        }

        // Find step by key
        $step = $instance->workflow->steps()->where('step_key', $request->step_key)->first();
        if (!$step) {
            return response()->json([
                'success' => false,
                'message' => 'Step not found'
            ], 404);
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
            'data' => $this->navigationService->formatSuggestionForResponse($suggestion),
            'message' => 'Step completed successfully'
        ]);
    }

    /**
     * Get workflow instance status (Mobile API).
     */
    public function getStatus(Request $request, int $workflowInstanceId): JsonResponse
    {
        $user = auth()->guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $progress = $this->workflowService->getWorkflowProgress($workflowInstanceId);
        
        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Workflow instance not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $progress,
            'message' => 'Workflow status retrieved successfully'
        ]);
    }

    /**
     * Get workflow progress for display (Mobile API).
     */
    public function getProgress(Request $request, int $workflowInstanceId): JsonResponse
    {
        $user = auth()->guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $progress = $this->workflowService->getWorkflowProgress($workflowInstanceId);
        
        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Workflow instance not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $progress,
            'message' => 'Workflow progress retrieved successfully'
        ]);
    }

    /**
     * Log a workflow action (client-side logging) - Mobile API.
     */
    public function logAction(Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'workflow_instance_id' => 'required|exists:workflow_instances,id',
            'action_type' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $this->navigationService->logAction(
            $request->workflow_instance_id,
            $request->action_type,
            $request->metadata ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Action logged successfully'
        ]);
    }
}

