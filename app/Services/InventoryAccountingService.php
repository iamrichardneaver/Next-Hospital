<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\LabPurchaseOrder;
use App\Models\PharmacyPurchaseOrder;
use App\Models\RadiologyPurchaseOrder;

class InventoryAccountingService
{
    public const PHARMACY_CATEGORY_CODE = 'PHARM_STOCK';

    public const LAB_CATEGORY_CODE = 'LAB_SUPPLIES';

    public const RADIOLOGY_CATEGORY_CODE = 'RADIOLOGY_SUPPLIES';

    /**
     * @param  array<int, array{name: string, quantity: float|int, unit_cost: float, line_total: float}>  $lines
     */
    public function recordPharmacyReceipt(PharmacyPurchaseOrder $order, array $lines, int $userId): ?Expense
    {
        return $this->recordReceipt(
            $order->branch_id,
            $order->po_number,
            $order->supplier?->name,
            self::PHARMACY_CATEGORY_CODE,
            'Pharmacy inventory purchase',
            $lines,
            $userId
        );
    }

    /**
     * @param  array<int, array{name: string, quantity: float|int, unit_cost: float, line_total: float}>  $lines
     */
    public function recordLabReceipt(LabPurchaseOrder $order, array $lines, int $userId): ?Expense
    {
        return $this->recordReceipt(
            $order->branch_id,
            $order->po_number,
            $order->supplier?->name,
            self::LAB_CATEGORY_CODE,
            'Laboratory supplies purchase',
            $lines,
            $userId
        );
    }

    /**
     * @param  array<int, array{name: string, quantity: float|int, unit_cost: float, line_total: float}>  $lines
     */
    public function recordRadiologyReceipt(RadiologyPurchaseOrder $order, array $lines, int $userId): ?Expense
    {
        return $this->recordReceipt(
            $order->branch_id,
            $order->po_number,
            $order->supplier?->name,
            self::RADIOLOGY_CATEGORY_CODE,
            'Radiology supplies purchase',
            $lines,
            $userId
        );
    }

    /**
     * @param  array<int, array{name: string, quantity: float|int, unit_cost: float, line_total: float}>  $lines
     */
    protected function recordReceipt(
        int $branchId,
        string $poNumber,
        ?string $vendorName,
        string $categoryCode,
        string $descriptionPrefix,
        array $lines,
        int $userId
    ): ?Expense {
        $amount = round(collect($lines)->sum('line_total'), 2);
        if ($amount <= 0) {
            return null;
        }

        $category = ExpenseCategory::where('code', $categoryCode)->where('is_active', true)->first();
        if (!$category) {
            throw new \RuntimeException("Expense category {$categoryCode} is not configured.");
        }

        $lineDetail = collect($lines)
            ->map(fn ($line) => sprintf(
                '%s × %s @ GH₵%s = GH₵%s',
                $line['name'],
                $line['quantity'],
                number_format((float) $line['unit_cost'], 2),
                number_format((float) $line['line_total'], 2)
            ))
            ->implode("\n");

        $department = match ($categoryCode) {
            self::PHARMACY_CATEGORY_CODE => 'pharmacy',
            self::RADIOLOGY_CATEGORY_CODE => 'radiology',
            default => 'lab',
        };

        $expense = Expense::create([
            'category_id' => $category->id,
            'branch_id' => $branchId,
            'department' => $department,
            'amount' => $amount,
            'expense_date' => now()->toDateString(),
            'description' => "{$descriptionPrefix} — {$poNumber}",
            'reference' => $poNumber,
            'vendor' => $vendorName,
            'notes' => "Auto-recorded on goods receipt.\n\n{$lineDetail}",
            'created_by' => $userId,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
        $expense->status = 'approved';
        $expense->save();

        return $expense;
    }

    public function getInventoryPurchaseTotals(?int $branchId, string $startDate, string $endDate): array
    {
        $codes = [self::PHARMACY_CATEGORY_CODE, self::LAB_CATEGORY_CODE, self::RADIOLOGY_CATEGORY_CODE];

        $rows = Expense::query()
            ->approved()
            ->byDateRange($startDate, $endDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn ($q) => $q->whereIn('code', $codes))
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.code, expense_categories.name, SUM(expenses.amount) as total, COUNT(*) as count')
            ->groupBy('expense_categories.id', 'expense_categories.code', 'expense_categories.name')
            ->get()
            ->keyBy('code');

        $pharmacy = (float) ($rows[self::PHARMACY_CATEGORY_CODE]->total ?? 0);
        $lab = (float) ($rows[self::LAB_CATEGORY_CODE]->total ?? 0);
        $radiology = (float) ($rows[self::RADIOLOGY_CATEGORY_CODE]->total ?? 0);

        return [
            'pharmacy' => round($pharmacy, 2),
            'lab' => round($lab, 2),
            'radiology' => round($radiology, 2),
            'total' => round($pharmacy + $lab + $radiology, 2),
            'pharmacy_count' => (int) ($rows[self::PHARMACY_CATEGORY_CODE]->count ?? 0),
            'lab_count' => (int) ($rows[self::LAB_CATEGORY_CODE]->count ?? 0),
            'radiology_count' => (int) ($rows[self::RADIOLOGY_CATEGORY_CODE]->count ?? 0),
        ];
    }
}
