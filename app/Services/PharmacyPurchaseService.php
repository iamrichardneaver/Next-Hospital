<?php

namespace App\Services;

use App\Models\DrugStock;
use App\Models\PharmacyPurchaseOrder;
use App\Models\PharmacyPurchaseOrderItem;
use Illuminate\Support\Facades\DB;

class PharmacyPurchaseService
{
    public function __construct(private InventoryAccountingService $accountingService)
    {
    }

    public function createOrder(array $data, array $items, int $userId): PharmacyPurchaseOrder
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            $total = collect($items)->sum(fn ($item) => $item['quantity_ordered'] * $item['unit_cost']);

            $order = PharmacyPurchaseOrder::create([
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
                PharmacyPurchaseOrderItem::create([
                    'pharmacy_purchase_order_id' => $order->id,
                    'drug_id' => $item['drug_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_cost' => $item['unit_cost'],
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);
            }

            return $order->load(['items.drug', 'supplier', 'branch']);
        });
    }

    public function markOrdered(PharmacyPurchaseOrder $order, int $userId): PharmacyPurchaseOrder
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

    public function cancelOrder(PharmacyPurchaseOrder $order, int $userId): PharmacyPurchaseOrder
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
     * @param  array<int, array{quantity: int, batch_number?: ?string, expiry_date?: ?string}>  $receipts  keyed by item id
     */
    public function receiveGoods(PharmacyPurchaseOrder $order, array $receipts, int $userId): PharmacyPurchaseOrder
    {
        if (!$order->canReceive()) {
            throw new \RuntimeException('This purchase order is not open for receiving.');
        }

        return DB::transaction(function () use ($order, $receipts, $userId) {
            $order->load('items.drug', 'supplier');
            $receiptLines = [];

            foreach ($order->items as $item) {
                $receipt = $receipts[$item->id] ?? null;
                if (!$receipt || (int) ($receipt['quantity'] ?? 0) <= 0) {
                    continue;
                }

                $qty = (int) $receipt['quantity'];
                if ($qty > $item->remainingQuantity()) {
                    throw new \RuntimeException("Receive quantity exceeds remaining for {$item->drug->name}.");
                }

                $batchNumber = $receipt['batch_number'] ?? $item->batch_number;
                $expiryDate = $receipt['expiry_date'] ?? $item->expiry_date;
                $unitCost = (float) $item->unit_cost;

                $this->receiveToDrugStock(
                    $item->drug_id,
                    $order->branch_id,
                    $qty,
                    $unitCost,
                    $batchNumber,
                    $expiryDate,
                    $order->supplier->name,
                    $userId
                );

                $item->update([
                    'quantity_received' => $item->quantity_received + $qty,
                    'batch_number' => $batchNumber,
                    'expiry_date' => $expiryDate,
                ]);

                $receiptLines[] = [
                    'name' => $item->drug->name,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => round($qty * $unitCost, 2),
                ];
            }

            if ($receiptLines === []) {
                throw new \RuntimeException('Enter at least one quantity to receive.');
            }

            $this->accountingService->recordPharmacyReceipt($order, $receiptLines, $userId);
            $this->refreshOrderStatus($order, $userId);

            return $order->fresh(['items.drug', 'supplier', 'branch']);
        });
    }

    public function receiveToDrugStock(
        int $drugId,
        int $branchId,
        int $quantity,
        float $unitCost,
        ?string $batchNumber,
        $expiryDate,
        ?string $supplierName,
        int $userId
    ): DrugStock {
        $stock = DrugStock::where('drug_id', $drugId)
            ->where('branch_id', $branchId)
            ->first();

        if ($stock) {
            $stock->incrementStock($quantity);
            $stock->update([
                'cost_price' => $unitCost,
                'batch_number' => $batchNumber ?? $stock->batch_number,
                'expiry_date' => $expiryDate ?? $stock->expiry_date,
                'supplier' => $supplierName ?? $stock->supplier,
                'updated_by' => $userId,
            ]);

            return $stock->fresh();
        }

        return DrugStock::create([
            'drug_id' => $drugId,
            'branch_id' => $branchId,
            'current_stock' => $quantity,
            'minimum_stock' => 10,
            'maximum_stock' => 100,
            'reorder_level' => 20,
            'batch_number' => $batchNumber,
            'expiry_date' => $expiryDate,
            'supplier' => $supplierName,
            'cost_price' => $unitCost,
            'selling_price' => $unitCost * 1.2,
            'is_active' => true,
            'created_by' => $userId,
        ]);
    }

    protected function refreshOrderStatus(PharmacyPurchaseOrder $order, int $userId): void
    {
        $order->load('items');
        $allReceived = $order->items->every(fn ($item) => $item->isFullyReceived());
        $anyReceived = $order->items->contains(fn ($item) => $item->quantity_received > 0);

        $status = $allReceived ? 'received' : ($anyReceived ? 'partially_received' : $order->status);

        $order->update([
            'status' => $status,
            'received_at' => in_array($status, ['received', 'partially_received'], true) ? now() : $order->received_at,
            'updated_by' => $userId,
        ]);
    }
}
