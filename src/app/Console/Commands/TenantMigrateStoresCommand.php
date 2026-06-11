<?php

namespace App\Console\Commands;

use App\Models\Platform\HotelRegistry;
use App\Services\Tenancy\TenantConnectionManager;
use App\Services\Tenancy\TenantSchemaProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Run tenant migrations on standalone store databases (not listed in tenant_registry).
 */
class TenantMigrateStoresCommand extends Command
{
    protected $signature = 'tenant:migrate-stores
                            {--hotel= : Only migrate mairy-tentant-store-{hotel_id}}
                            {--discover : Also scan MySQL for mairy-tentant-store-* schemas without hotel_registry row}';

    protected $description = 'Run pending tenant migrations on standalone store DBs (legacy import / store provision)';

    public function handle(TenantSchemaProvisioner $provisioner): int
    {
        $hotelFilter = $this->option('hotel');
        $dbNames = $this->resolveStoreDatabaseNames($hotelFilter, (bool) $this->option('discover'));

        if ($dbNames === []) {
            $this->warn('No store tenant databases to migrate.');

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($dbNames as $dbName) {
            $this->line("Migrating {$dbName}…");

            if (! TenantConnectionManager::databaseExists($dbName)) {
                $this->warn("  Skipped: database does not exist.");
                $failed++;

                continue;
            }

            try {
                $provisioner->migrate($dbName);
                $this->info('  OK');
            } catch (\Throwable $e) {
                $this->error('  '.$e->getMessage());
                $failed++;
            }
        }

        if ($failed > 0) {
            $this->error("{$failed} store DB(s) failed.");

            return self::FAILURE;
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function resolveStoreDatabaseNames(?string $hotelFilter, bool $discover): array
    {
        if ($hotelFilter !== null && $hotelFilter !== '') {
            return [TenantConnectionManager::databaseNameForStore((int) $hotelFilter)];
        }

        $names = HotelRegistry::query()
            ->whereNull('chain_id')
            ->pluck('db_name')
            ->filter(static fn ($name) => is_string($name) && $name !== '')
            ->unique()
            ->values()
            ->all();

        if (! $discover) {
            return $names;
        }

        $prefix = (string) config('chain_registry.tenant_db_prefix', 'mairy-');
        $like = $prefix.'tentant-store-%';
        $central = (string) config('tenancy.database.central_connection', 'platform');

        $rows = DB::connection($central)->select(
            'SELECT SCHEMA_NAME AS db_name FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE ?',
            [$like]
        );

        foreach ($rows as $row) {
            $names[] = (string) $row->db_name;
        }

        return array_values(array_unique($names));
    }
}
