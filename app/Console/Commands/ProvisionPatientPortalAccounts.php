<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Services\PatientPortalAccountService;
use Illuminate\Console\Command;

class ProvisionPatientPortalAccounts extends Command
{
    protected $signature = 'patients:provision-portal
                            {--execute : Actually create portal accounts (default is dry-run)}
                            {--limit= : Limit number of patients processed}';

    protected $description = 'Provision patient portal accounts for patients without linked user_id (dry-run by default)';

    public function handle(PatientPortalAccountService $portalService): int
    {
        $execute = (bool) $this->option('execute');
        $limit = $this->option('limit');

        $query = Patient::query()->whereNull('user_id')->orderBy('id');
        if ($limit) {
            $query->limit((int) $limit);
        }

        $patients = $query->get(['id', 'first_name', 'last_name', 'patient_number', 'phone', 'email']);
        $count = $patients->count();

        if ($count === 0) {
            $this->info('No patients without portal accounts found.');
            return self::SUCCESS;
        }

        $this->info(($execute ? 'Executing' : 'Dry-run') . ": {$count} patient(s) without portal user_id.");

        if (!$execute) {
            $this->table(
                ['ID', 'Patient #', 'Name', 'Phone'],
                $patients->map(fn ($p) => [
                    $p->id,
                    $p->patient_number ?? '-',
                    trim($p->first_name . ' ' . $p->last_name),
                    $p->phone ?? '-',
                ])->toArray()
            );
            $this->warn('No changes made. Re-run with --execute to provision accounts.');
            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        foreach ($patients as $patient) {
            $result = $portalService->ensurePortalUserForPatient($patient);
            if ($result['created']) {
                $created++;
                $this->line("Created portal account for patient #{$patient->id} ({$result['email']})");
            } else {
                $skipped++;
            }
        }

        $this->info("Done. Created: {$created}, already linked/skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
