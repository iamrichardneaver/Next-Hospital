<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InsuranceProvider;
use App\Models\InsuranceServiceCategory;
use App\Models\InsuranceCoveragePolicy;
use App\Models\User;

class InsuranceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $admin = User::where('email', 'admin@hospital.com')->first();
        $adminId = $admin ? $admin->id : 1;

        // Create Insurance Providers
        $providers = [
            [
                'name' => 'National Health Insurance Scheme (NHIS)',
                'code' => 'NHIS',
                'type' => 'nhis',
                'contact_person' => 'Dr. Samuel Mensah',
                'phone' => '+233 302 123456',
                'email' => 'info@nhis.gov.gh',
                'address' => 'NHIS Head Office, Accra, Ghana',
                'website' => 'https://www.nhis.gov.gh',
                'default_coverage_percentage' => 70.00,
                'default_co_pay_percentage' => 30.00,
                'requires_pre_authorization' => true,
                'supports_electronic_claims' => true,
                'supports_real_time_verification' => true,
                'is_active' => true,
                'created_by' => $adminId
            ],
            [
                'name' => 'Vida Health Insurance',
                'code' => 'VIDA',
                'type' => 'private',
                'contact_person' => 'Ms. Akosua Asante',
                'phone' => '+233 302 234567',
                'email' => 'info@vidahealth.com',
                'address' => 'Vida Health Insurance, Accra, Ghana',
                'website' => 'https://www.vidahealth.com',
                'default_coverage_percentage' => 80.00,
                'default_co_pay_percentage' => 20.00,
                'requires_pre_authorization' => false,
                'supports_electronic_claims' => true,
                'supports_real_time_verification' => true,
                'is_active' => true,
                'created_by' => $adminId
            ],
            [
                'name' => 'Metropolitan Health Insurance',
                'code' => 'METRO',
                'type' => 'private',
                'contact_person' => 'Mr. Kwame Nkrumah',
                'phone' => '+233 302 345678',
                'email' => 'info@metrohealth.com',
                'address' => 'Metropolitan Health Insurance, Accra, Ghana',
                'website' => 'https://www.metrohealth.com',
                'default_coverage_percentage' => 85.00,
                'default_co_pay_percentage' => 15.00,
                'requires_pre_authorization' => true,
                'supports_electronic_claims' => true,
                'supports_real_time_verification' => false,
                'is_active' => true,
                'created_by' => $adminId
            ],
            [
                'name' => 'Ghana Health Service (GHS)',
                'code' => 'GHS',
                'type' => 'government',
                'contact_person' => 'Dr. Patrick Kuma-Aboagye',
                'phone' => '+233 302 456789',
                'email' => 'info@ghs.gov.gh',
                'address' => 'GHS Head Office, Accra, Ghana',
                'website' => 'https://www.ghs.gov.gh',
                'default_coverage_percentage' => 100.00,
                'default_co_pay_percentage' => 0.00,
                'requires_pre_authorization' => false,
                'supports_electronic_claims' => false,
                'supports_real_time_verification' => false,
                'is_active' => true,
                'created_by' => $adminId
            ],
            [
                'name' => 'Enterprise Life Insurance',
                'code' => 'ENTERPRISE',
                'type' => 'private',
                'contact_person' => 'Ms. Grace Addo',
                'phone' => '+233 302 567890',
                'email' => 'info@enterpriselife.com',
                'address' => 'Enterprise Life Insurance, Accra, Ghana',
                'website' => 'https://www.enterpriselife.com',
                'default_coverage_percentage' => 75.00,
                'default_co_pay_percentage' => 25.00,
                'requires_pre_authorization' => true,
                'supports_electronic_claims' => true,
                'supports_real_time_verification' => true,
                'is_active' => true,
                'created_by' => $adminId
            ]
        ];

        foreach ($providers as $providerData) {
            InsuranceProvider::create($providerData);
        }

        // Create Service Categories
        $categories = [
            [
                'name' => 'Consultation Services',
                'code' => 'CONSULT',
                'description' => 'General and specialist consultations',
                'is_active' => true,
                'sort_order' => 1
            ],
            [
                'name' => 'Laboratory Services',
                'code' => 'LAB',
                'description' => 'Laboratory tests and diagnostics',
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'name' => 'Pharmacy Services',
                'code' => 'PHARM',
                'description' => 'Medications and pharmaceutical products',
                'is_active' => true,
                'sort_order' => 3
            ],
            [
                'name' => 'Radiology Services',
                'code' => 'RADIO',
                'description' => 'X-rays, MRI, CT scans, and imaging',
                'is_active' => true,
                'sort_order' => 4
            ],
            [
                'name' => 'Surgical Services',
                'code' => 'SURG',
                'description' => 'Surgical procedures and operations',
                'is_active' => true,
                'sort_order' => 5
            ],
            [
                'name' => 'Emergency Services',
                'code' => 'EMERG',
                'description' => 'Emergency room and urgent care',
                'is_active' => true,
                'sort_order' => 6
            ],
            [
                'name' => 'Maternity Services',
                'code' => 'MATERN',
                'description' => 'Prenatal, delivery, and postnatal care',
                'is_active' => true,
                'sort_order' => 7
            ],
            [
                'name' => 'Pediatric Services',
                'code' => 'PEDIAT',
                'description' => 'Children and infant healthcare',
                'is_active' => true,
                'sort_order' => 8
            ]
        ];

        foreach ($categories as $categoryData) {
            InsuranceServiceCategory::create($categoryData);
        }

        // Create Coverage Policies for each provider
        $providers = InsuranceProvider::all();
        $categories = InsuranceServiceCategory::all();

        foreach ($providers as $provider) {
            foreach ($categories as $category) {
                // Different coverage rates based on provider type and service
                $coveragePercentage = $this->getCoveragePercentage($provider->type, $category->code);
                $coPayPercentage = 100 - $coveragePercentage;

                InsuranceCoveragePolicy::create([
                    'insurance_provider_id' => $provider->id,
                    'service_category_id' => $category->id,
                    'service_type' => strtolower($category->code),
                    'coverage_percentage' => $coveragePercentage,
                    'co_pay_percentage' => $coPayPercentage,
                    'max_coverage_amount' => $this->getMaxCoverageAmount($provider->type, $category->code),
                    'min_coverage_amount' => 0,
                    'deductible' => $this->getDeductible($provider->type),
                    'requires_pre_authorization' => $this->requiresPreAuth($provider->type, $category->code),
                    'pre_authorization_days' => $this->getPreAuthDays($provider->type, $category->code),
                    'is_active' => true,
                    'effective_from' => now()->subYear()->toDateString(),
                    'effective_until' => now()->addYear()->toDateString(),
                    'created_by' => $adminId
                ]);
            }
        }

        $this->command->info('Insurance data seeded successfully!');
    }

    private function getCoveragePercentage($providerType, $serviceCode)
    {
        $coverageRates = [
            'nhis' => [
                'CONSULT' => 70,
                'LAB' => 60,
                'PHARM' => 50,
                'RADIO' => 60,
                'SURG' => 80,
                'EMERG' => 90,
                'MATERN' => 100,
                'PEDIAT' => 80
            ],
            'government' => [
                'CONSULT' => 100,
                'LAB' => 100,
                'PHARM' => 100,
                'RADIO' => 100,
                'SURG' => 100,
                'EMERG' => 100,
                'MATERN' => 100,
                'PEDIAT' => 100
            ],
            'private' => [
                'CONSULT' => 80,
                'LAB' => 75,
                'PHARM' => 70,
                'RADIO' => 80,
                'SURG' => 85,
                'EMERG' => 90,
                'MATERN' => 90,
                'PEDIAT' => 85
            ]
        ];

        return $coverageRates[$providerType][$serviceCode] ?? 70;
    }

    private function getMaxCoverageAmount($providerType, $serviceCode)
    {
        $maxAmounts = [
            'nhis' => [
                'CONSULT' => 200,
                'LAB' => 500,
                'PHARM' => 300,
                'RADIO' => 1000,
                'SURG' => 5000,
                'EMERG' => 2000,
                'MATERN' => 3000,
                'PEDIAT' => 1000
            ],
            'government' => null, // No limit
            'private' => [
                'CONSULT' => 500,
                'LAB' => 1000,
                'PHARM' => 800,
                'RADIO' => 2000,
                'SURG' => 10000,
                'EMERG' => 5000,
                'MATERN' => 8000,
                'PEDIAT' => 2000
            ]
        ];

        return $maxAmounts[$providerType][$serviceCode] ?? null;
    }

    private function getDeductible($providerType)
    {
        $deductibles = [
            'nhis' => 0,
            'government' => 0,
            'private' => 50
        ];

        return $deductibles[$providerType] ?? 0;
    }

    private function requiresPreAuth($providerType, $serviceCode)
    {
        $preAuthRequired = [
            'nhis' => ['SURG', 'RADIO', 'EMERG'],
            'government' => [],
            'private' => ['SURG', 'RADIO']
        ];

        return in_array($serviceCode, $preAuthRequired[$providerType] ?? []);
    }

    private function getPreAuthDays($providerType, $serviceCode)
    {
        if (!$this->requiresPreAuth($providerType, $serviceCode)) {
            return null;
        }

        $preAuthDays = [
            'nhis' => 7,
            'private' => 3
        ];

        return $preAuthDays[$providerType] ?? 5;
    }
}
