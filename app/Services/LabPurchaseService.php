<?php

namespace App\Services;

use App\Models\LabConsumable;
use App\Models\LabInventoryMovement;
use App\Models\LabInventoryStock;
use App\Models\LabInventoryTransaction;
use App\Models\LabPurchaseOrder;
use App\Models\LabPurchaseOrderItem;
use App\Models\LabReagent;
use Illuminate\Support\Facades\DB;

class LabPurchaseService
{
    public function __construct(private InventoryAccountingService $accountingService)
    {
    }

    public function createOrder(array $data, array $items, int $userId): LabPurchaseOrder
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            $total = collect($items)->sum(fn ($item) => $item['quantity_ordered'] * $item['unit_cost']);

            $order = LabPurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'],
                'status' => $data['status'] ?? 'draft',
                'total_amount' => $total,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
                'ordered_by' => ($data['status'] ?? 'draft') === 'ordered' ? $userId : null,
                'ordered_at' => ($data['status'] ?? 'draft') === 'ordered' ? now() : null,
            ]);

            foreach ($items as $item) {
                LabPurchaseOrderItem::create([
                    'lab_purchase_order_id' => $order->id,
                    'item_type' => $item['item_type'],
                    'item_id' => $item['item_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_cost' => $item['unit_cost'],
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);
            }

            return $order->load(['items', 'supplier', 'branch']);
        });
    }

    public function markOrdered(LabPurchaseOrder $order, int $userId): LabPurchaseOrder
    {
        if (!$order->canMarkOrdered()) {
            throw new \RuntimeException('Only draft purchase orders can be marked as ordered.');
        }

        $order->update([
            'status' => 'ordered',
            'ordered_by' => $userId,
            'ordered_at' => now(),
            'updated_by' => $userId,
        ]);

        return $order->fresh();
    }

    public function cancelOrder(LabPurchaseOrder $order, int $userId): LabPurchaseOrder
    {
        if (!$order->canCancel()) {
            throw new \RuntimeException('This purchase order cannot be cancelled.');
        }

        $order->update([
            'status' => 'cancelled',
            'updated_by' => $userId,
        ]);

        return $order->fresh();
    }

    /**
     * @param  array<int, array{quantity: float, batch_number?: ?string, expiry_date?: ?string}>  $receipts
     */
    public function receiveGoods(LabPurchaseOrder $order, array $receipts, int $userId): LabPurchaseOrder
    {
        if (!$order->canReceive()) {
            throw new \RuntimeException('This purchase order is not open for receiving.');
        }

        return DB::transaction(function () use ($order, $receipts, $userId) {
            $order->load('items', 'supplier');
            $receiptLines = [];

            foreach ($order->items as $item) {
                $receipt = $receipts[$item->id] ?? null;
                if (!$receipt || (float) ($receipt['quantity'] ?? 0) <= 0) {
                    continue;
                }

                $qty = (float) $receipt['quantity'];
                if ($qty > $item->remainingQuantity()) {
                    throw new \RuntimeException("Receive quantity exceeds remaining for {$item->getItemName()}.");
                }

                $batchNumber = $receipt['batch_number'] ?? $item->batch_number;
                $expiryDate = $receipt['expiry_date'] ?? $item->expiry_date;
                $unitCost = (float) $item->unit_cost;

                $this->receiveToLabStock(
                    $item->item_type,
                    (int) $item->item_id,
                    $order->branch_id,
                    $qty,
                    $unitCost,
                    $batchNumber,
                    $expiryDate,
                    $order->supplier_id,
                    $order->id,
                    $order->po_number,
                    $userId
                );

                $item->update([
                    'quantity_received' => (float) $item->quantity_received + $qty,
                    'batch_number' => $batchNumber,
                    'expiry_date' => $expiryDate,
                ]);

                $receiptLines[] = [
                    'name' => $item->getItemName(),
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => round($qty * $unitCost, 2),
                ];
            }

            if ($receiptLines === []) {
                throw new \RuntimeException('Enter at least one quantity to receive.');
            }

            $this->accountingService->recordLabReceipt($order, $receiptLines, $userId);
            $this->refreshOrderStatus($order, $userId);

            return $order->fresh(['items', 'supplier', 'branch']);
        });
    }

    public function receiveToLabStock(
        string $itemType,
        int $itemId,
        int $branchId,
        float $quantity,
        float $unitCost,
        ?string $batchNumber,
        $expiryDate,
        ?int $supplierId,
        int $purchaseOrderId,
        ?string $referenceNumber,
        int $userId
    ): LabInventoryStock {
        $stockQuery = LabInventoryStock::where('branch_id', $branchId)
            ->where('item_type', $itemType)
            ->where('item_id', $itemId);

        if ($batchNumber) {
            $stockQuery->where('batch_number', $batchNumber);
        } else {
            $stockQuery->whereNull('batch_number');
        }

        $stock = $stockQuery->first();

        if ($stock) {
            $stock->update([
                'quantity' => (float) $stock->quantity + $quantity,
                'unit_cost' => $unitCost,
                'expiry_date' => $expiryDate ?? $stock->expiry_date,
                'updated_by' => $userId,
            ]);
        } else {
            $stock = LabInventoryStock::create([
                'branch_id' => $branchId,
                'item_type' => $itemType,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'batch_number' => $batchNumber,
                'expiry_date' => $expiryDate,
                'unit_cost' => $unitCost,
                'created_by' => $userId,
            ]);
        }

        $this->incrementCatalogStock($itemType, $itemId, $quantity, $unitCost, $batchNumber, $expiryDate, $userId);

        LabInventoryTransaction::create([
            'item_id' => $itemId,
            'item_type' => $itemType,
            'transaction_type' => 'purchase',
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'reference_number' => $referenceNumber,
            'supplier_id' => $supplierId,
            'location' => "branch:{$branchId}",
            'notes' => 'Goods receipt from lab purchase order',
            'transaction_date' => now()->toDateString(),
            'created_by' => $userId,
        ]);

        LabInventoryMovement::create([
            'branch_id' => $branchId,
            'item_type' => $itemType,
            'item_id' => $itemId,
            'quantity' => $quantity,
            'movement_type' => 'purchase_receipt',
            'reference_type' => LabPurchaseOrder::class,
            'reference_id' => $purchaseOrderId,
            'performed_by' => $userId,
            'notes' => "Goods receipt from {$referenceNumber}",
        ]);

        return $stock;
    }

    protected function incrementCatalogStock(
        string $itemType,
        int $itemId,
        float $quantity,
        float $unitCost,
        ?string $batchNumber,
        $expiryDate,
        int $userId
    ): void {
        if ($itemType === 'reagent') {
            $item = LabReagent::findOrFail($itemId);
        } else {
            $item = LabConsumable::findOrFail($itemId);
        }

        $item->update([
            'current_stock' => (float) $item->current_stock + $quantity,
            'unit_cost' => $unitCost,
            'batch_number' => $batchNumber ?? $item->batch_number,
            'expiry_date' => $expiryDate ?? $item->expiry_date,
            'updated_by' => $userId,
        ]);
    }

    protected function refreshOrderStatus(LabPurchaseOrder $order, int $userId): void
    {
        $order->load('items');
        $allReceived = $order->items->every(fn ($item) => $item->isFullyReceived());
        $anyReceived = $order->items->contains(fn ($item) => (float) $item->quantity_received > 0);

        $status = $allReceived ? 'received' : ($anyReceived ? 'partially_received' : $order->status);

        $order->update([
            'status' => $status,
            'received_at' => in_array($status, ['received', 'partially_received'], true) ? now() : $order->received_at,
            'updated_by' => $userId,
        ]);
    }
}
