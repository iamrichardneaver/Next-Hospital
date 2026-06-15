<?php

namespace App\Http\Controllers\Concerns;

use App\Services\WorkflowService;
use App\Services\WorkflowNavigationService;
use App\Models\WorkflowInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

trait WorkflowNavigation
{
    protected WorkflowService $workflowService;
    protected WorkflowNavigationService $navigationService;

    /**
     * Initialize workflow services.
     */
    protected function initializeWorkflowServices(): void
    {
        $this->workflowService = app(WorkflowService::class);
        $this->navigationService = app(WorkflowNavigationService::class);
    }

    /**
     * Initialize workflow for an entity.
     */
    protected function initializeWorkflowForEntity($entity, string $workflowName): ?WorkflowInstance
    {
        if (!isset($this->workflowService)) {
            $this->initializeWorkflowServices();
        }

        $entityType = $this->getEntityType($entity);
        $entityId = $entity->id;
        $userId = auth()->id();

        $instance = $this->workflowService->initializeWorkflow($workflowName, $entityType, $entityId, $userId);

        if ($instance && method_exists($entity, 'update')) {
            $entity->update(['workflow_instance_id' => $instance->id]);
        }

        return $instance;
    }

    /**
     * Complete a workflow step.
     */
    protected function completeWorkflowStep($entity, string $stepKey, array $metadata = []): ?WorkflowInstance
    {
        if (!isset($this->workflowService)) {
            $this->initializeWorkflowServices();
        }

        $instance = $this->getWorkflowInstance($entity);
        if (!$instance) {
            return null;
        }

        // Complete the step (advances through skipped intermediate steps when needed)
        $nextStep = $this->workflowService->completeStepByKey(
            $instance->id,
            $stepKey,
            auth()->id(),
            $metadata
        );

        // Refresh instance
        $instance->refresh();

        return $instance;
    }

    /**
     * Get next step response (for AJAX requests).
     */
    protected function getNextStepResponse($entity, string $successMessage = 'Action completed successfully'): JsonResponse
    {
        if (!isset($this->navigationService)) {
            $this->initializeWorkflowServices();
        }

        $instance = $this->getWorkflowInstance($entity);
        if (!$instance) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'workflow' => null,
            ]);
        }

        $suggestion = $this->navigationService->getNextStepSuggestion($instance->id, auth()->id());

        return response()->json([
            'success' => true,
            'message' => $successMessage,
            'workflow' => $this->navigationService->formatSuggestionForResponse($suggestion),
        ]);
    }

    /**
     * Redirect to next step (for regular requests).
     */
    protected function redirectToNextStep($entity, string $successMessage = 'Action completed successfully'): RedirectResponse
    {
        if (!isset($this->navigationService)) {
            $this->initializeWorkflowServices();
        }

        $instance = $this->getWorkflowInstance($entity);
        if (!$instance) {
            return redirect()->back()->with('success', $successMessage);
        }

        $suggestion = $this->navigationService->getNextStepSuggestion($instance->id, auth()->id());

        if (!$suggestion) {
            return redirect()->back()->with('success', $successMessage);
        }

        // Check if auto-redirect
        if ($suggestion['step']['auto_redirect'] && $suggestion['route']['url']) {
            return redirect($suggestion['route']['url'])->with('success', $successMessage);
        }

        // Return with workflow suggestion data
        return redirect()->back()->with([
            'success' => $successMessage,
            'workflow_next_step' => $suggestion,
        ]);
    }

    /**
     * Get workflow instance for entity.
     */
    protected function getWorkflowInstance($entity): ?WorkflowInstance
    {
        if (!isset($this->workflowService)) {
            $this->initializeWorkflowServices();
        }

        // Try to get from entity relationship
        if (isset($entity->workflow_instance_id)) {
            return WorkflowInstance::find($entity->workflow_instance_id);
        }

        // Try to get by entity type and id
        $entityType = $this->getEntityType($entity);
        return $this->workflowService->getInstanceForEntity($entityType, $entity->id);
    }

    /**
     * Get entity type from model.
     */
    protected function getEntityType($entity): string
    {
        $className = class_basename($entity);
        
        // Map common model names to entity types
        $mapping = [
            'Visit' => 'visit',
            'Consultation' => 'consultation',
            'LabRequest' => 'lab_request',
            'Prescription' => 'prescription',
            'Invoice' => 'invoice',
        ];

        return $mapping[$className] ?? strtolower($className);
    }

    /**
     * Determine workflow name based on entity and context.
     */
    protected function determineWorkflowName($entity): string
    {
        $entityType = $this->getEntityType($entity);

        // Map entity types to workflow names
        $mapping = [
            'visit' => $this->getVisitWorkflowName($entity),
            'consultation' => 'OPD Consultation',
            'lab_request' => 'Lab Test',
            'prescription' => 'Pharmacy Dispensing',
            'invoice' => 'Billing',
        ];

        return $mapping[$entityType] ?? 'Default Workflow';
    }

    /**
     * Get workflow name for visit based on visit type.
     */
    protected function getVisitWorkflowName($visit): string
    {
        if (!isset($visit->visit_type)) {
            return 'OPD Visit';
        }

        $mapping = [
            'OPD' => 'OPD Visit',
            'IPD' => 'IPD Admission',
            'Emergency' => 'Emergency Visit',
            'LabOnly' => 'Lab Test',
            'PharmacyOnly' => 'Pharmacy Dispensing',
            'RadiologyOnly' => 'Radiology Walk-in',
        ];

        return $mapping[$visit->visit_type] ?? 'OPD Visit';
    }

    /**
     * Force-complete workflow when visit/consultation is closed or cancelled.
     */
    protected function completeWorkflowForEntity($entity, string $reason = 'entity_completed'): bool
    {
        if (!isset($this->workflowService)) {
            $this->initializeWorkflowServices();
        }

        $entityType = $this->getEntityType($entity);

        return $this->workflowService->completeWorkflowForEntity(
            $entityType,
            $entity->id,
            auth()->id(),
            $reason
        );
    }
}

