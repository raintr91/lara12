<?php

namespace App\Console\Commands;

use App\Models\Platform\Chain;
use App\Services\Tenancy\ChainProvisioner;
use Illuminate\Console\Command;

class TenantCreateSchemaCommand extends Command
{
    protected $signature = 'tenant:create-schema {chain : Chain id on platform DB}';

    protected $description = 'Create tenant database from chain naming config and run tenant migrations';

    public function handle(ChainProvisioner $provisioner): int
    {
        $chain = Chain::query()->find((int) $this->argument('chain'));

        if (! $chain) {
            $this->error('Chain not found on platform DB.');

            return self::FAILURE;
        }

        $registry = $provisioner->provision($chain);

        $this->info("Tenant ready: {$registry->db_name} (status={$registry->status})");

        return self::SUCCESS;
    }
}
