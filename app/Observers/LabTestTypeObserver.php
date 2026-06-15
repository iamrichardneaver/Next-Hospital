<?php

namespace App\Observers;

use App\Models\LabTestType;
use App\Services\LabTemplateSyncService;

class LabTestTypeObserver
{
    public function __construct(private LabTemplateSyncService $syncService)
    {
    }

    public function saved(LabTestType $testType): void
    {
        $this->syncService->onTestTypeSaved($testType);
    }
}
