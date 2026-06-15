<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Paystack = 'paystack';
    case MobileMoneyOffline = 'mobile_money_offline';

    // Legacy values kept for backward compatibility with existing records
    case Card = 'card';
    case Momo = 'momo';
    case BankTransfer = 'bank_transfer';
    case Insurance = 'insurance';

    /**
     * Standard staff-facing payment methods.
     */
    public static function staffMethods(): array
    {
        return [
            self::Cash,
            self::Paystack,
            self::MobileMoneyOffline,
        ];
    }

    /**
     * All values accepted when recording a payment.
     */
    public static function allValues(): array
    {
        return array_map(fn (self $m) => $m->value, self::cases());
    }

    /**
     * Laravel validation rule for payment_method fields.
     */
    public static function validationRule(bool $staffOnly = false): string
    {
        $values = $staffOnly
            ? array_map(fn (self $m) => $m->value, self::staffMethods())
            : self::allValues();

        return 'in:' . implode(',', $values);
    }

    /**
     * Normalize legacy aliases to canonical values.
     */
    public static function normalize(?string $method): ?string
    {
        if ($method === null || $method === '') {
            return null;
        }

        $method = strtolower(trim($method));

        return match ($method) {
            'mobile_money', 'mobile_money_offline', 'momo', 'ussd' => self::MobileMoneyOffline->value,
            'paystack', 'card', 'online' => self::Paystack->value,
            'cash' => self::Cash->value,
            'bank_transfer', 'bank' => self::BankTransfer->value,
            'insurance', 'nhis' => self::Insurance->value,
            default => $method,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Paystack => 'Paystack (Online)',
            self::MobileMoneyOffline => 'Mobile Money (Offline)',
            self::Card => 'Card (Legacy)',
            self::Momo => 'Mobile Money (Legacy)',
            self::BankTransfer => 'Bank Transfer',
            self::Insurance => 'Insurance',
        };
    }

    public static function labelFor(?string $method): string
    {
        if (!$method) {
            return 'Unknown';
        }

        $normalized = self::normalize($method);

        foreach (self::cases() as $case) {
            if ($case->value === $normalized) {
                return $case->label();
            }
        }

        return ucfirst(str_replace('_', ' ', $normalized));
    }

    public static function requiresOfflineReference(string $method): bool
    {
        return self::normalize($method) === self::MobileMoneyOffline->value;
    }

    public static function isOnline(string $method): bool
    {
        return self::normalize($method) === self::Paystack->value;
    }

    /**
     * Validate metadata requirements before recording a payment.
     *
     * @throws \InvalidArgumentException
     */
    public static function validateRecording(string $method, array $metadata = []): void
    {
        $normalized = self::normalize($method);

        if (!in_array($normalized, self::allValues(), true)) {
            throw new \InvalidArgumentException('Invalid payment method: ' . $method);
        }

        if (self::requiresOfflineReference($normalized)) {
            $reference = $metadata['reference_number']
                ?? $metadata['transaction_reference']
                ?? $metadata['momo_reference']
                ?? null;

            if (empty($reference)) {
                throw new \InvalidArgumentException(
                    'Transaction reference is required for offline mobile money payments.'
                );
            }
        }
    }

    /**
     * Group key for reporting (maps legacy values to standard buckets).
     */
    public static function reportingGroup(?string $method): string
    {
        $normalized = self::normalize($method ?? '');

        return match ($normalized) {
            self::Cash->value => 'cash',
            self::Paystack->value => 'paystack',
            self::MobileMoneyOffline->value => 'mobile_money_offline',
            self::Insurance->value => 'insurance',
            self::BankTransfer->value => 'bank_transfer',
            default => $normalized ?: 'other',
        };
    }
}
