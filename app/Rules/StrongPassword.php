<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Strong Password Validation Rule
 * 
 * Enforces a strong password policy:
 * - Minimum 8 characters
 * - At least one uppercase letter
 * - At least one lowercase letter
 * - At least one number
 * - At least one special character
 */
class StrongPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Minimum length check
        if (strlen($value) < 8) {
            $fail('The :attribute must be at least 8 characters long.');
            return;
        }

        // Maximum length check
        if (strlen($value) > 64) {
            $fail('The :attribute must not exceed 64 characters.');
            return;
        }

        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $value)) {
            $fail('The :attribute must contain at least one uppercase letter.');
            return;
        }

        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $value)) {
            $fail('The :attribute must contain at least one lowercase letter.');
            return;
        }

        // Check for at least one number
        if (!preg_match('/[0-9]/', $value)) {
            $fail('The :attribute must contain at least one number.');
            return;
        }

        // Check for at least one special character
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value)) {
            $fail('The :attribute must contain at least one special character (!@#$%^&*(),.?":{}|<>).');
            return;
        }

        // Check for common weak passwords
        $weakPasswords = [
            'password', 'password123', '12345678', 'qwerty123', 
            'admin123', 'welcome123', 'letmein123', 'Password1!',
            'Admin123!', 'Welcome1!', 'Password123!'
        ];

        if (in_array(strtolower($value), array_map('strtolower', $weakPasswords))) {
            $fail('The :attribute is too common. Please choose a more unique password.');
            return;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must be at least 8 characters and contain uppercase, lowercase, number, and special character.';
    }
}
