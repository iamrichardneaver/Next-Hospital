<?php

namespace App\Observers;

use App\Models\LabRequest;
use App\Services\LabInventoryService;
use App\Services\LabTemplateSyncService;
use Illuminate\Support\Facades\Session;

class LabRequestObserver
{
    public function __construct(
        private LabTemplateSyncService $syncService,
        private LabInventoryService $inventoryService
    ) {
    }

    public function created(LabRequest $labRequest): void
    {
        $this->syncService->onLabRequestCreated($labRequest);

        if ($labRequest->status === 'completed') {
            $this->handleInventoryOnStatusChange($labRequest);
        }
    }

    public function updated(LabRequest $labRequest): void
    {
        if ($labRequest->wasChanged('test_type_id') && !$labRequest->template_id) {
            $this->syncService->syncLabRequest($labRequest);
            return;
        }

        if ($labRequest->wasChanged('template_id') && $labRequest->template_id) {
            $labRequest->addTemplates([$labRequest->template_id]);
        }

        if ($labRequest->wasChanged('status')) {
            $this->handleInventoryOnStatusChange($labRequest);
        }
    }

    protected function handleInventoryOnStatusChange(LabRequest $labRequest): void
    {
        if ($labRequest->status === 'completed' && !$labRequest->inventory_deducted_at) {
            $result = $this->inventoryService->deductForLabRequest($labRequest);
            if ($result['warnings'] !== []) {
                Session::flash('inventory_warning', implode(' ', $result['warnings']));
            }
            return;
        }

        if ($labRequest->status === 'cancelled' && $labRequest->inventory_deducted_at) {
            $this->inventoryService->reverseDeduction($labRequest);
        }
    }
}
