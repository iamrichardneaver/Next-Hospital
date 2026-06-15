<?php

namespace App\Support;

use Illuminate\Http\Request;

class PaymentMetadata
{
    /**
     * Extract payment metadata from a request (cash change, offline MoMo details, Paystack refs).
     */
    public static function fromRequest(Request $request, array $base = []): array
    {
        $metadata = $base;

        if ($request->filled('amount_tendered')) {
            $metadata['amount_tendered'] = (float) $request->input('amount_tendered');
        }

        if ($request->filled('change_due')) {
            $metadata['change_due'] = (float) $request->input('change_due');
        }

        if ($request->filled('momo_phone')) {
            $metadata['momo_phone'] = $request->input('momo_phone');
        }

        if ($request->filled('momo_network')) {
            $metadata['momo_network'] = $request->input('momo_network');
        }

        if ($request->filled('momo_reference')) {
            $metadata['momo_reference'] = $request->input('momo_reference');
            $metadata['reference_number'] = $request->input('momo_reference');
        }

        if ($request->filled('reference_number') && empty($metadata['reference_number'])) {
            $metadata['reference_number'] = $request->input('reference_number');
        }

        if ($request->filled('payment_reference')) {
            $metadata['reference_number'] = $request->input('payment_reference');
        }

        if ($request->filled('transaction_id')) {
            $metadata['transaction_id'] = $request->input('transaction_id');
        }

        return $metadata;
    }
}
