<?php

namespace App\Services;

use App\Models\LabRequest;
use App\Models\LabTestTemplate;
use App\Models\LabTestType;
use Illuminate\Support\Facades\Log;

class LabTemplateSyncService
{
    /**
     * Link a test type to its matching template (persists template_id when resolved).
     */
    public function linkTestType(LabTestType $testType): ?int
    {
        return $testType->getResolvedTemplateId();
    }

    /**
     * Link unlinked test types that match this template via resolveTemplate().
     */
    public function linkTestTypesForTemplate(LabTestTemplate $template): int
    {
        $linked = 0;

        LabTestType::whereNull('template_id')->chunkById(100, function ($testTypes) use ($template, &$linked) {
            foreach ($testTypes as $testType) {
                $resolved = $testType->resolveTemplate();
                if ($resolved && (int) $resolved->id === (int) $template->id) {
                    $testType->update(['template_id' => $template->id]);
                    $this->syncLabRequestsForTestType($testType);
                    $linked++;
                }
            }
        });

        return $linked;
    }

    /**
     * Sync template for a single lab request missing a template.
     */
    public function syncLabRequest(LabRequest $labRequest): bool
    {
        if ($labRequest->template_id || !$labRequest->test_type_id) {
            return false;
        }

        $testType = $labRequest->relationLoaded('testType')
            ? $labRequest->testType
            : $labRequest->testType()->first();

        if (!$testType) {
            return false;
        }

        $templateId = $this->linkTestType($testType);
        if (!$templateId) {
            return false;
        }

        $labRequest->template_id = $templateId;
        $labRequest->save();
        $labRequest->addTemplates([$templateId]);

        return true;
    }

    /**
     * Sync lab requests for one test type that are missing templates.
     */
    public function syncLabRequestsForTestType(LabTestType $testType): array
    {
        $stats = ['updated' => 0, 'skipped' => 0, 'errors' => 0];

        $templateId = $this->linkTestType($testType);
        if (!$templateId) {
            return $stats;
        }

        LabRequest::whereNull('template_id')
            ->where('test_type_id', $testType->id)
            ->chunkById(100, function ($labRequests) use (&$stats, $templateId) {
                foreach ($labRequests as $labRequest) {
                    try {
                        $labRequest->template_id = $templateId;
                        $labRequest->save();
                        $labRequest->addTemplates([$templateId]);
                        $stats['updated']++;
                    } catch (\Exception $e) {
                        $stats['errors']++;
                        Log::error('Lab template sync failed for request ' . $labRequest->request_number, [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $stats;
    }

    /**
     * Full sync for all lab requests missing templates (admin/debug).
     */
    public function syncAllLabRequests(bool $dryRun = false): array
    {
        $stats = ['updated' => 0, 'skipped' => 0, 'errors' => 0];

        LabRequest::whereNull('template_id')
            ->whereNotNull('test_type_id')
            ->with('testType')
            ->chunkById(100, function ($labRequests) use ($dryRun, &$stats) {
                foreach ($labRequests as $labRequest) {
                    if (!$labRequest->testType) {
                        $stats['skipped']++;
                        continue;
                    }

                    $templateId = $labRequest->testType->getResolvedTemplateId();
                    if (!$templateId) {
                        $stats['skipped']++;
                        continue;
                    }

                    try {
                        if (!$dryRun) {
                            $labRequest->template_id = $templateId;
                            $labRequest->save();
                            $labRequest->addTemplates([$templateId]);
                        }
                        $stats['updated']++;
                    } catch (\Exception $e) {
                        $stats['errors']++;
                        Log::error('Lab template sync failed for request ' . $labRequest->request_number, [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $stats;
    }

    /**
     * After a test type is saved: resolve template link and sync its pending requests.
     */
    public function onTestTypeSaved(LabTestType $testType): void
    {
        if (!$testType->wasRecentlyCreated && !$testType->wasChanged(['test_name', 'test_code', 'template_id'])) {
            return;
        }

        $this->linkTestType($testType);
        $this->syncLabRequestsForTestType($testType);
    }

    /**
     * After a template is saved: link matching test types and sync their requests.
     */
    public function onTemplateSaved(LabTestTemplate $template): void
    {
        if (!$template->wasRecentlyCreated && !$template->wasChanged(['template_name', 'template_code'])) {
            return;
        }

        $this->linkTestTypesForTemplate($template);
    }

    /**
     * After a lab request is created: ensure template and pivot rows are set.
     */
    public function onLabRequestCreated(LabRequest $labRequest): void
    {
        if (!$labRequest->template_id && $labRequest->test_type_id) {
            $this->syncLabRequest($labRequest);
            return;
        }

        if ($labRequest->template_id) {
            $labRequest->addTemplates([$labRequest->template_id]);
        }
    }
}
