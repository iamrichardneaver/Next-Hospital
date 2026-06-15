<?php

namespace App\Http\Controllers\Concerns;

use App\Services\PricingService;

trait ProcessesInvoiceItems
{
    /**
     * Remove blank manual line items submitted with the form.
     */
    protected function filterEmptyManualItems(?array $items): array
    {
        if (empty($items)) {
            return [];
        }

        return array_values(array_filter($items, function ($item) {
            $description = trim((string) ($item['description'] ?? ''));
            if ($description === '') {
                return false;
            }

            return floatval($item['quantity'] ?? 0) > 0;
        }));
    }

    /**
     * Process invoice items and ensure proper calculation.
     */
    protected function processInvoiceItems(array $items): array
    {
        $processedItems = [];

        foreach ($items as $item) {
            $quantity = floatval($item['quantity'] ?? 1);
            $unitPrice = floatval($item['unit_price'] ?? 0);
            $total = $quantity * $unitPrice;

            $processedItems[] = [
                'description' => $item['description'] ?? 'Service',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $total,
                'name' => $item['description'] ?? 'Service',
            ];
        }

        return $processedItems;
    }

    /**
     * Process selected services and convert to items with dynamic pricing.
     */
    protected function processSelectedServices(array $selectedServices, ?int $patientId = null, ?int $branchId = null): array
    {
        $items = [];
        $patientId = $patientId ?? request()->get('patient_id');
        $branchId = $branchId ?? request()->get('branch_id') ?? auth()->user()?->branches()->first()?->id;
        $pricingService = $this->pricingService ?? app(PricingService::class);

        foreach ($selectedServices as $service) {
            $quantity = floatval($service['quantity'] ?? 1);
            $basePrice = floatval($service['price'] ?? 0);
            $finalPrice = $basePrice;

            if ($patientId && $branchId && isset($service['id'])) {
                try {
                    $serviceId = $this->getServiceIdForPricing($service);
                    if ($serviceId) {
                        $pricing = $pricingService->calculateServicePrice(
                            $serviceId,
                            $patientId,
                            $branchId,
                            ['quantity' => $quantity, 'service_type' => $service['type'] ?? 'general']
                        );
                        $finalPrice = $pricing['final_price'] ?? $basePrice;
                    }
                } catch (\Exception $e) {
                    \Log::debug('PricingService failed for service, using base price', [
                        'service_id' => $service['id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $total = $quantity * $finalPrice;

            $items[] = [
                'description' => $service['name'] ?? 'Service',
                'quantity' => $quantity,
                'unit_price' => $finalPrice,
                'total' => $total,
                'name' => $service['name'] ?? 'Service',
                'type' => $service['type'] ?? 'service',
            ];
        }

        return $items;
    }

    /**
     * Get service ID for pricing service based on service type.
     */
    protected function getServiceIdForPricing(array $service): mixed
    {
        $type = $service['type'] ?? 'general';
        $id = $service['id'] ?? null;

        return match ($type) {
            'lab_test' => "lab_test_{$id}",
            'drug' => "drug_{$id}",
            'consultation' => 'consultation',
            'radiology' => "radiology_{$id}",
            'service_pricing' => $id,
            default => $id,
        };
    }

    /**
     * Calculate totals from items.
     */
    protected function calculateTotals(array $items, ?float $taxAmount = null, ?float $discountAmount = null): array
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += floatval($item['total'] ?? 0);
        }

        $tax = $taxAmount ?? 0;
        $discount = $discountAmount ?? 0;
        $total = $subtotal + $tax - $discount;

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
        ];
    }
}
