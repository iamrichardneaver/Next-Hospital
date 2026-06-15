<?php

namespace App\Observers;

use App\Models\LabTestTemplate;
use App\Services\LabTemplateSyncService;

class LabTestTemplateObserver
{
    public function __construct(private LabTemplateSyncService $syncService)
    {
    }

    public function saved(LabTestTemplate $template): void
    {
        $this->syncService->onTemplateSaved($template);
    }
}
