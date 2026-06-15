<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\IdPrefixService;
use App\Models\IdPrefixSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for IdPrefixService
 * 
 * Tests the ID generation service that creates human-readable IDs
 * for various entities like patients, visits, invoices, etc.
 */
class IdPrefixServiceTest extends TestCase
{
    use RefreshDatabase;

    protected IdPrefixService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IdPrefixService();
    }

    /**
     * Test ID generation for patient entity
     */
    public function test_generates_patient_id_correctly()
    {
        // Create a patient ID prefix setting
        IdPrefixSetting::create([
            'entity_type' => 'patient',
            'prefix' => 'PT',
            'separator' => '-',
            'year_format' => 'YYYY',
            'sequence_length' => 5,
            'current_sequence' => 0,
            'is_active' => true,
        ]);

        $patientId = $this->service->generateId('patient');

        $this->assertMatchesRegularExpression('/^PT-\d{4}-\d{5}$/', $patientId);
        $this->assertStringContainsString(date('Y'), $patientId);
    }

    /**
     * Test ID generation increments sequence
     */
    public function test_id_generation_increments_sequence()
    {
        IdPrefixSetting::create([
            'entity_type' => 'visit',
            'prefix' => 'VST',
            'separator' => '-',
            'year_format' => 'YYYYMMDD',
            'sequence_length' => 3,
            'current_sequence' => 0,
            'is_active' => true,
        ]);

        $id1 = $this->service->generateId('visit');
        $id2 = $this->service->generateId('visit');
        $id3 = $this->service->generateId('visit');

        $this->assertStringEndsWith('-001', $id1);
        $this->assertStringEndsWith('-002', $id2);
        $this->assertStringEndsWith('-003', $id3);
    }

    /**
     * Test sequence resets daily
     */
    public function test_sequence_resets_for_new_day()
    {
        $setting = IdPrefixSetting::create([
            'entity_type' => 'invoice',
            'prefix' => 'INV',
            'separator' => '-',
            'year_format' => 'YYYYMMDD',
            'sequence_length' => 4,
            'current_sequence' => 100,
            'last_reset_date' => now()->subDay(), // Yesterday
            'is_active' => true,
        ]);

        $id = $this->service->generateId('invoice');

        // Should reset to 001 for new day
        $this->assertStringEndsWith('-0001', $id);

        $updatedSetting = IdPrefixSetting::where('entity_type', 'invoice')->first();
        $this->assertEquals(1, $updatedSetting->current_sequence);
    }

    /**
     * Test handles missing entity type gracefully
     */
    public function test_handles_missing_entity_type()
    {
        $id = $this->service->generateId('nonexistent_entity');

        // Should return a default UUID-based ID
        $this->assertNotEmpty($id);
        $this->assertIsString($id);
    }

    /**
     * Test locked settings cannot be modified
     */
    public function test_locked_settings_cannot_generate_ids()
    {
        IdPrefixSetting::create([
            'entity_type' => 'lab_test',
            'prefix' => 'LAB',
            'separator' => '-',
            'year_format' => 'YYYY',
            'sequence_length' => 4,
            'current_sequence' => 0,
            'is_locked' => true, // Locked
            'is_active' => true,
        ]);

        $this->expectException(\Exception::class);
        $this->service->generateId('lab_test');
    }

    /**
     * Test validates prefix pattern
     */
    public function test_validates_prefix_pattern()
    {
        $isValid = $this->service->validatePattern('PT-{YYYY}-{###}');
        $this->assertTrue($isValid);

        $isInvalid = $this->service->validatePattern('INVALID_{PATTERN}');
        $this->assertFalse($isInvalid);
    }

    /**
     * Test generates correct format with different separators
     */
    public function test_generates_with_different_separators()
    {
        IdPrefixSetting::create([
            'entity_type' => 'prescription',
            'prefix' => 'RX',
            'separator' => '/',
            'year_format' => 'YY',
            'sequence_length' => 3,
            'current_sequence' => 0,
            'is_active' => true,
        ]);

        $id = $this->service->generateId('prescription');

        $this->assertMatchesRegularExpression('/^RX\/\d{2}\/\d{3}$/', $id);
    }

    /**
     * Test concurrent ID generation doesn't create duplicates
     */
    public function test_concurrent_generation_no_duplicates()
    {
        IdPrefixSetting::create([
            'entity_type' => 'consultation',
            'prefix' => 'CON',
            'separator' => '-',
            'year_format' => 'YYYYMMDD',
            'sequence_length' => 4,
            'current_sequence' => 0,
            'is_active' => true,
        ]);

        $ids = [];
        for ($i = 0; $i < 100; $i++) {
            $ids[] = $this->service->generateId('consultation');
        }

        $uniqueIds = array_unique($ids);
        $this->assertCount(100, $uniqueIds, 'All IDs should be unique');
    }

    /**
     * Test reset sequence functionality
     */
    public function test_reset_sequence()
    {
        $setting = IdPrefixSetting::create([
            'entity_type' => 'appointment',
            'prefix' => 'APT',
            'separator' => '-',
            'year_format' => 'YYYY',
            'sequence_length' => 5,
            'current_sequence' => 500,
            'is_active' => true,
        ]);

        $this->service->resetSequence('appointment');

        $updatedSetting = IdPrefixSetting::where('entity_type', 'appointment')->first();
        $this->assertEquals(0, $updatedSetting->current_sequence);
    }
}
