<?php

namespace App\Console\Commands;

use App\Models\Tenancy\ChainTenant;
use App\Services\Tenancy\ChainProvisioner;
use App\Services\Tenancy\TenantAggregator;
use Illuminate\Console\Command;

class TenantMigrateTentantCommand extends Command
{
    protected $signature = 'tenant:migrate-tentant
                            {--chain= : Only migrate this chain_id}
                            {--pretend : Output migrate SQL without running}';

    protected $description = 'Run pending chain migrations on all active tenant databases (via Stancl tenants:migrate)';

    public function handle(ChainProvisioner $provisioner): int
    {
        $chainFilter = $this->option('chain');
        $tenants = TenantAggregator::activeTenants();

        if ($chainFilter !== null && $chainFilter !== '') {
            $tenants = $tenants->where('chain_id', (int) $chainFilter);
        }

        if ($tenants->isEmpty()) {
            $this->warn('No active tenants in tenant_registry.');

            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            /** @var ChainTenant $tenant */
            $this->line("Migrating {$tenant->db_name} (chain_id={$tenant->chain_id})…");

            if ($this->option('pretend')) {
                $this->warn('Pretend is not supported with tenants:migrate; run migrate on a single chain without --pretend.');

                continue;
            }

            $provisioner->migrateExisting($tenant);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
