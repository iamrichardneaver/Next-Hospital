<?php

namespace App\Traits;

trait SafePatientAccess
{
    /**
     * Safely get patient name with fallback
     */
    public function getPatientName($visit, $fallback = 'Unknown Patient')
    {
        if (!$visit || !$visit->patient) {
            return $fallback;
        }
        
        $firstName = $visit->patient->first_name ?? '';
        $lastName = $visit->patient->last_name ?? '';
        
        return trim($firstName . ' ' . $lastName) ?: $fallback;
    }
    
    /**
     * Safely get patient number with fallback
     */
    public function getPatientNumber($visit, $fallback = 'N/A')
    {
        if (!$visit || !$visit->patient) {
            return $fallback;
        }
        
        return $visit->patient->patient_number ?? $fallback;
    }
    
    /**
     * Safely get patient NHIS number with fallback
     */
    public function getPatientNhisNumber($visit, $fallback = null)
    {
        if (!$visit || !$visit->patient) {
            return $fallback;
        }
        
        return $visit->patient->nhis_number ?? $fallback;
    }
    
    /**
     * Check if patient exists
     */
    public function hasPatient($visit)
    {
        return $visit && $visit->patient;
    }
    
    /**
     * Get patient link URL safely
     */
    public function getPatientLink($visit, $route = 'patients.show')
    {
        if (!$visit || !$visit->patient) {
            return '#';
        }
        
        return route($route, $visit->patient_id);
    }
    
    /**
     * Get patient display data as array
     */
    public function getPatientDisplayData($visit)
    {
        if (!$visit || !$visit->patient) {
            return [
                'name' => 'Patient Not Found',
                'number' => 'ID: ' . ($visit->patient_id ?? 'N/A'),
                'nhis' => null,
                'has_patient' => false,
                'link' => '#'
            ];
        }
        
        return [
            'name' => $this->getPatientName($visit),
            'number' => $this->getPatientNumber($visit),
            'nhis' => $this->getPatientNhisNumber($visit),
            'has_patient' => true,
            'link' => $this->getPatientLink($visit)
        ];
    }
}
