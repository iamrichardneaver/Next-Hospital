<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class CheckDuplicatePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:check-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for duplicate permissions in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for duplicate permissions...');
        
        // Find duplicate permission names
        $duplicates = DB::table('permissions')
            ->select('name', DB::raw('COUNT(*) as count'))
            ->groupBy('name')
            ->having('count', '>', 1)
            ->get();
        
        if ($duplicates->isEmpty()) {
            $this->info('✅ No duplicate permissions found!');
            return 0;
        }
        
        $this->warn('⚠️  Found ' . $duplicates->count() . ' duplicate permission name(s):');
        
        foreach ($duplicates as $duplicate) {
            $this->line("  - {$duplicate->name} (appears {$duplicate->count} times)");
            
            // Get all instances
            $instances = Permission::where('name', $duplicate->name)->get();
            
            foreach ($instances as $instance) {
                $this->line("    ID: {$instance->id}, Guard: {$instance->guard_name}, Created: {$instance->created_at}");
            }
        }
        
        $this->warn("\n⚠️  You should remove duplicate permissions to avoid conflicts.");
        $this->info("Run: php artisan permissions:remove-duplicates to automatically fix this.");
        
        return 1;
    }
}
