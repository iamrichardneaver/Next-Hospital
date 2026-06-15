<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LabRequest;
use App\Models\LabTestType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FixLabRequestTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lab:fix-templates {--dry-run : Show what would be updated without making changes} {--show-matches : Show detailed matching information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix lab requests by matching test names to test types and assigning templates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $showMatches = $this->option('show-matches');
        
        if ($dryRun) {
            $this->warn('🔍 Running in DRY RUN mode - no changes will be made');
            $this->newLine();
        }

        $this->info('🔍 Finding lab requests with missing test_type_id or template_id...');
        $this->newLine();

        // Get all test types with templates
        $testTypes = LabTestType::whereNotNull('template_id')
            ->with('template')
            ->get();

        if ($testTypes->isEmpty()) {
            $this->error('❌ No test types have templates assigned!');
            $this->info('💡 Please assign templates to test types first at: /lab/management/test-types');
            return 1;
        }

        // Get lab requests needing fixes
        $labRequests = LabRequest::where(function($query) {
            $query->whereNull('test_type_id')
                  ->orWhereNull('template_id');
        })
        ->whereNotNull('test_type') // Must have test_type text to match against
        ->get();

        if ($labRequests->isEmpty()) {
            $this->info('✅ No lab requests need fixing!');
            return 0;
        }

        $this->info("📋 Found {$labRequests->count()} lab requests to process");
        $this->newLine();

        $updated = 0;
        $matched = 0;
        $unmatched = 0;
        $errors = 0;
        $unmatchedRequests = [];

        $progressBar = $this->output->createProgressBar($labRequests->count());
        $progressBar->start();

        foreach ($labRequests as $labRequest) {
            $progressBar->advance();
            
            $testTypeName = $labRequest->test_type;
            $matchedTestType = null;

            // Try to find matching test type
            foreach ($testTypes as $testType) {
                // Try exact match first
                if (strcasecmp($testType->test_name, $testTypeName) === 0) {
                    $matchedTestType = $testType;
                    break;
                }

                // Try partial match (contains)
                if (stripos($testTypeName, $testType->test_name) !== false || 
                    stripos($testType->test_name, $testTypeName) !== false) {
                    $matchedTestType = $testType;
                    break;
                }

                // Try matching test code (if it's in the name)
                if (stripos($testTypeName, $testType->test_code) !== false) {
                    $matchedTestType = $testType;
                    break;
                }
            }

            if ($matchedTestType) {
                $matched++;
                
                if ($showMatches) {
                    $this->newLine();
                    $this->line("  ✓ Matched: '{$testTypeName}' → '{$matchedTestType->test_name}' (Template: {$matchedTestType->template->template_name})");
                }

                if (!$dryRun) {
                    try {
                        $labRequest->update([
                            'test_type_id' => $matchedTestType->id,
                            'template_id' => $matchedTestType->template_id
                        ]);
                        $updated++;
                    } catch (\Exception $e) {
                        $errors++;
                        if ($showMatches) {
                            $this->error("  ✗ Error updating {$labRequest->request_number}: {$e->getMessage()}");
                        }
                    }
                }
            } else {
                $unmatched++;
                $unmatchedRequests[] = [
                    'request_number' => $labRequest->request_number,
                    'test_type' => $testTypeName
                ];
                
                if ($showMatches) {
                    $this->newLine();
                    $this->line("  ✗ No match found for: '{$testTypeName}'");
                }
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->info('📊 === Summary ===');
        $this->table(
            ['Status', 'Count'],
            [
                ['Total lab requests processed', $labRequests->count()],
                ['Successfully matched' . ($dryRun ? ' (would update)' : ' and updated'), $matched],
                ['Could not match', $unmatched],
                ['Errors', $errors],
            ]
        );

        // Show unmatched requests
        if ($unmatched > 0) {
            $this->newLine();
            $this->warn("⚠️  {$unmatched} lab requests could not be auto-matched:");
            $this->table(
                ['Request Number', 'Test Type Name'],
                array_slice($unmatchedRequests, 0, 10) // Show first 10
            );
            
            if ($unmatched > 10) {
                $this->line("   ... and " . ($unmatched - 10) . " more");
            }
            
            $this->newLine();
            $this->info('💡 These need manual template assignment via:');
            $this->line('   1. Edit lab request: /lab/{id}/edit');
            $this->line('   2. Select template from dropdown');
            $this->line('   3. Save changes');
        }

        if ($dryRun && $matched > 0) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN. Run without --dry-run to apply changes.');
            $this->info('   Command: php artisan lab:fix-templates');
        }

        if (!$dryRun && $updated > 0) {
            $this->newLine();
            $this->info("✅ Successfully fixed {$updated} lab requests!");
            $this->info('   Lab technicians can now enter results for these requests.');
        }

        return 0;
    }
}
