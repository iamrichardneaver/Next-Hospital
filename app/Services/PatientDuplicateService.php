<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Facades\Log;

/**
 * Patient Duplicate Detection Service
 * 
 * Intelligently detects potential duplicate patients based on:
 * - Phone number (when provided)
 * - Email address (when provided)
 * - NHIS number (when provided)
 * - Name + Date of Birth combination (when both provided)
 * - Name similarity (fuzzy matching)
 * 
 * This service is used to prevent duplicate patient creation across:
 * - Web staff registration (email/phone optional)
 * - API staff registration (email/phone optional)
 * - Mobile app patient self-registration (email/phone required)
 */
class PatientDuplicateService
{
    /**
     * Check for duplicate patients based on provided data
     * 
     * @param array $patientData Patient data to check
     * @param int|null $excludeId Patient ID to exclude from check (for updates)
     * @param int|null $branchId Branch ID to filter by (optional)
     * @return array Result with 'is_duplicate' flag and 'matches' array
     */
    public function checkForDuplicates(array $patientData, ?int $excludeId = null, ?int $branchId = null): array
    {
        $matches = [];
        
        // Normalize phone number (remove spaces, dashes, etc.)
        $phone = $this->normalizePhone($patientData['phone'] ?? null);
        $email = $this->normalizeEmail($patientData['email'] ?? null);
        $nhisNumber = $this->normalizeString($patientData['nhis_number'] ?? null);
        $ghanaCardNumber = $this->normalizeString($patientData['ghana_card_number'] ?? null);
        $firstName = $this->normalizeString($patientData['first_name'] ?? null);
        $lastName = $this->normalizeString($patientData['last_name'] ?? null);
        $dateOfBirth = $patientData['date_of_birth'] ?? null;
        
        // High-confidence identifiers are checked globally (same person may visit any branch).
        $globalQuery = Patient::query();
        if ($excludeId) {
            $globalQuery->where('id', '!=', $excludeId);
        }

        // Name similarity is branch-scoped to reduce false positives from common names.
        $branchQuery = Patient::query();
        if ($excludeId) {
            $branchQuery->where('id', '!=', $excludeId);
        }
        if ($branchId) {
            $branchQuery->where('branch_id', $branchId);
        }
        
        // 1. Check by phone number (if provided)
        if ($phone) {
            $phoneMatches = $this->findByPhone($globalQuery, $phone);
            foreach ($phoneMatches as $match) {
                $this->appendMatch($matches, $match, 'Phone number match', 'high', 'phone', $phone, ' and Phone number match');
            }
        }
        
        // 2. Check by email address (if provided)
        if ($email) {
            $emailMatches = $this->findByEmail($globalQuery, $email);
            foreach ($emailMatches as $match) {
                $this->appendMatch($matches, $match, 'Email address match', 'high', 'email', $email, ' and Email address match');
            }
        }
        
        // 3. Check by NHIS number (if provided)
        if ($nhisNumber) {
            $nhisMatches = $this->findByNhis($globalQuery, $nhisNumber);
            foreach ($nhisMatches as $match) {
                $this->appendMatch($matches, $match, 'NHIS number match', 'high', 'nhis_number', $nhisNumber, ' and NHIS number match');
            }
        }
        
        // 4. Check by Ghana Card number (if provided)
        if ($ghanaCardNumber) {
            $ghanaCardMatches = $this->findByGhanaCard($globalQuery, $ghanaCardNumber);
            if ($ghanaCardMatches->isNotEmpty()) {
                foreach ($ghanaCardMatches as $match) {
                    $this->appendMatch($matches, $match, 'Ghana Card number match', 'high', 'ghana_card_number', $ghanaCardNumber, ' and Ghana Card number match');
                }
            }
        }

        // 5. Check by Name + Date of Birth combination (if both provided)
        if ($firstName && $lastName && $dateOfBirth) {
            $nameDobMatches = $this->findByNameAndDob($globalQuery, $firstName, $lastName, $dateOfBirth);
            foreach ($nameDobMatches as $match) {
                $this->appendMatch(
                    $matches,
                    $match,
                    'Name and Date of Birth match',
                    'high',
                    'name_dob',
                    "{$firstName} {$lastName} - {$dateOfBirth}",
                    ' and Name/DOB match'
                );
            }
        }
        
        // 6. Check by name similarity (fuzzy matching) - only if no high-confidence matches found
        // This is a fallback for cases where phone/email/NHIS are not provided
        if (empty($matches) && $firstName && $lastName) {
            $nameMatches = $this->findByNameSimilarity($branchQuery, $firstName, $lastName);
            if ($nameMatches->isNotEmpty()) {
                foreach ($nameMatches as $patient) {
                    $matches[] = [
                        'patient' => $patient,
                        'reason' => 'Similar name',
                        'confidence' => 'medium',
                        'match_field' => 'name_similarity',
                        'match_value' => "{$firstName} {$lastName}",
                        'similarity_score' => null // Will be calculated in formatMatchesForResponse if needed
                    ];
                }
            }
        }
        
        // Remove duplicate patient entries (keep only one entry per patient)
        $uniqueMatches = collect($matches)->unique(function ($match) {
            return $match['patient']->id;
        })->values()->all();
        
        $hasHighConfidence = collect($uniqueMatches)->contains(function ($match) {
            return $match['confidence'] === 'high';
        });
        
        // Only block creation when there is a high-confidence match (same phone, email, NHIS, or name+DOB).
        // Same or similar first/last name alone does NOT block: multiple patients can share the same name.
        return [
            'is_duplicate' => $hasHighConfidence,
            'matches' => $uniqueMatches,
            'count' => count($uniqueMatches),
            'has_high_confidence_match' => $hasHighConfidence,
        ];
    }
    
    /**
     * Append or merge a duplicate match entry for a patient.
     */
    private function appendMatch(
        array &$matches,
        Patient $match,
        string $reason,
        string $confidence,
        string $matchField,
        string $matchValue,
        string $mergeSuffix
    ): void {
        $index = collect($matches)->search(function ($m) use ($match) {
            return $m['patient']->id === $match->id;
        });

        if ($index === false) {
            $matches[] = [
                'patient' => $match,
                'reason' => $reason,
                'confidence' => $confidence,
                'match_field' => $matchField,
                'match_value' => $matchValue,
            ];
            return;
        }

        if (!str_contains($matches[$index]['reason'], $reason)) {
            $matches[$index]['reason'] .= $mergeSuffix;
        }
    }

    /**
     * Find patients by phone number (exact normalized match only).
     */
    private function findByPhone($query, string $phone)
    {
        $normalizedPhone = $this->normalizePhone($phone);

        return (clone $query)
            ->with('branch')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereRaw('REPLACE(REPLACE(REPLACE(REPLACE(phone, " ", ""), "-", ""), "(", ""), ")", "") = ?', [$normalizedPhone])
            ->get();
    }
    
    /**
     * Find patients by email address
     */
    private function findByEmail($query, string $email)
    {
        $normalizedEmail = $this->normalizeEmail($email);
        
        return (clone $query)
            ->with('branch') // Eager load branch relationship
            ->where(function ($q) use ($normalizedEmail, $email) {
                $q->where('email', $normalizedEmail)
                  ->orWhere('email', $email)
                  ->orWhereRaw('LOWER(email) = ?', [strtolower($normalizedEmail)]);
            })
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();
    }
    
    /**
     * Find patients by NHIS number
     */
    private function findByNhis($query, string $nhisNumber)
    {
        $normalizedNhis = $this->normalizeString($nhisNumber);
        
        return (clone $query)
            ->with('branch') // Eager load branch relationship
            ->where(function ($q) use ($normalizedNhis, $nhisNumber) {
                $q->where('nhis_number', $normalizedNhis)
                  ->orWhere('nhis_number', $nhisNumber)
                  ->orWhereRaw('UPPER(TRIM(nhis_number)) = ?', [strtoupper($normalizedNhis)]);
            })
            ->whereNotNull('nhis_number')
            ->where('nhis_number', '!=', '')
            ->get();
    }
    
    /**
     * Find patients by Ghana Card number
     */
    private function findByGhanaCard($query, string $ghanaCardNumber)
    {
        $normalized = strtoupper($this->normalizeString($ghanaCardNumber));

        return (clone $query)
            ->with('branch')
            ->whereNotNull('ghana_card_number')
            ->where('ghana_card_number', '!=', '')
            ->whereRaw('UPPER(TRIM(ghana_card_number)) = ?', [$normalized])
            ->get();
    }

    /**
     * Find patients by name and date of birth (exact name match).
     */
    private function findByNameAndDob($query, string $firstName, string $lastName, string $dateOfBirth)
    {
        $normalizedFirstName = strtolower($this->normalizeString($firstName));
        $normalizedLastName = strtolower($this->normalizeString($lastName));

        return (clone $query)
            ->with('branch')
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [$normalizedFirstName])
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [$normalizedLastName])
            ->where('date_of_birth', $dateOfBirth)
            ->whereNotNull('date_of_birth')
            ->get();
    }
    
    /**
     * Find patients by name similarity (both first and last name must match).
     */
    private function findByNameSimilarity($query, string $firstName, string $lastName)
    {
        $normalizedFirstName = strtolower($this->normalizeString($firstName));
        $normalizedLastName = strtolower($this->normalizeString($lastName));

        if (strlen($normalizedFirstName) < 2 || strlen($normalizedLastName) < 2) {
            return collect();
        }

        $matches = (clone $query)
            ->with('branch')
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [$normalizedFirstName])
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [$normalizedLastName])
            ->get()
            ->map(function ($patient) use ($firstName, $lastName) {
                $similarity = 0;
                $matchCount = 0;

                if (!empty($patient->first_name) && !empty($firstName)) {
                    similar_text(strtolower($firstName), strtolower($patient->first_name), $firstSimilarity);
                    if ($firstSimilarity > 60) {
                        $similarity += $firstSimilarity;
                        $matchCount++;
                    }
                }

                if (!empty($patient->last_name) && !empty($lastName)) {
                    similar_text(strtolower($lastName), strtolower($patient->last_name), $lastSimilarity);
                    if ($lastSimilarity > 60) {
                        $similarity += $lastSimilarity;
                        $matchCount++;
                    }
                }

                $patient->similarity_score = $matchCount > 0 ? round($similarity / $matchCount, 2) : 0;

                return $patient;
            })
            ->filter(fn ($patient) => ($patient->similarity_score ?? 0) >= 80)
            ->sortByDesc('similarity_score')
            ->take(10);

        return $matches;
    }
    
    /**
     * Normalize phone number (remove spaces, dashes, parentheses)
     */
    private function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }
        
        return preg_replace('/[\s\-\(\)]/', '', trim($phone));
    }
    
    /**
     * Normalize email address (lowercase, trim)
     */
    private function normalizeEmail(?string $email): ?string
    {
        if (empty($email)) {
            return null;
        }
        
        return strtolower(trim($email));
    }
    
    /**
     * Normalize string (trim, remove extra spaces)
     */
    private function normalizeString(?string $string): ?string
    {
        if (empty($string)) {
            return null;
        }
        
        return trim(preg_replace('/\s+/', ' ', $string));
    }
    
    /**
     * Format duplicate matches for API response
     */
    public function formatMatchesForResponse(array $matches): array
    {
        return collect($matches)->map(function ($match) {
            $patient = $match['patient'];
            
            return [
                'id' => $patient->id,
                'patient_number' => $patient->patient_number,
                'full_name' => $patient->full_name,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'other_names' => $patient->other_names,
                'gender' => $patient->gender,
                'date_of_birth' => $patient->date_of_birth ? $patient->date_of_birth->format('Y-m-d') : null,
                'age' => $patient->age,
                'phone' => $patient->phone,
                'email' => $patient->email,
                'nhis_number' => $patient->nhis_number,
                'ghana_card_number' => $patient->ghana_card_number,
                'branch_name' => $patient->branch ? $patient->branch->name : 'N/A',
                'created_at' => $patient->created_at ? $patient->created_at->format('Y-m-d H:i:s') : null,
                'match_reason' => $match['reason'],
                'match_reasons' => preg_split('/\s+and\s+/', $match['reason']) ?: [$match['reason']],
                'confidence' => $match['confidence'],
                'match_field' => $match['match_field'],
                'match_value' => $match['match_value'] ?? null,
                'similarity_score' => $match['similarity_score'] ?? ($patient->similarity_score ?? null),
                'view_url' => route('patients.show', $patient->id),
                'check_in_url' => route('visits.create', ['patient_id' => $patient->id]),
                'api_url' => url('/api/patients/' . $patient->id),
            ];
        })->toArray();
    }
}
