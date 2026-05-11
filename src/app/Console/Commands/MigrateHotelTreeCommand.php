<?php

namespace App\Console\Commands;

use App\Jobs\MigrateHotelsRecursivelyJob;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MigrateHotelTreeCommand extends BaseCommand
{
    protected $signature = 'migrate:hotel-tree
        {--ids= : Comma-separated hotel IDs from source DB}
        {--source-db=db_local : Source database name}
        {--expand-parent-graph : Also expand recursive traversal from belongsTo parents to their children}
        {--sync : Run synchronously in current process}';

    protected $description = 'Recursively migrate hotels and their Eloquent relationships from source DB to target DB, skipping soft-deleted records.';

    public function handle(): int
    {
        $idsOption = (string) ($this->option('ids') ?? '');
        $sourceDb = (string) ($this->option('source-db') ?? 'db_local');
        $expandParentGraph = (bool) $this->option('expand-parent-graph');

        if ($idsOption === '') {
            $this->error('Missing --ids option. Example: --ids=398,344,375');
            return 1;
        }

        $hotelIds = array_values(array_filter(array_map('intval', explode(',', $idsOption))));
        if ($hotelIds === []) {
            $this->error('No valid hotel IDs parsed from --ids option.');
            return 1;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $this->syncReferenceTables($sourceDb);

            $job = new MigrateHotelsRecursivelyJob($hotelIds, $sourceDb, $expandParentGraph);

            if ((bool) $this->option('sync')) {
                $stats = $job->handle();
                $this->info('Migration finished (sync).');
                foreach ($stats as $table => $count) {
                    $this->line("- {$table}: {$count}");
                }
            } else {
                dispatch($job);
                $this->info('Migration job dispatched. Use queue worker to process it.');
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return 0;
    }

    private function syncReferenceTables(string $sourceDb): void
    {
        $this->bootstrapSourceConnection($sourceDb);

        // db_local has root countries with parent_id = null.
        DB::statement('ALTER TABLE countries MODIFY parent_id BIGINT NULL');

        $sourceCountries = DB::connection('mysql_source')
            ->table('countries')
            ->orderBy('id')
            ->get();

        foreach ($sourceCountries->chunk(500) as $chunk) {
            $rows = [];
            foreach ($chunk as $country) {
                $rows[] = Arr::except((array) $country, ['deleted_at']);
            }
            if ($rows !== []) {
                $updateColumns = array_values(array_filter(array_keys($rows[0]), static fn (string $column) => $column !== 'id'));
                DB::table('countries')->upsert($rows, ['id'], $updateColumns);
            }
        }
    }

    private function bootstrapSourceConnection(string $sourceDb): void
    {
        $mysqlConfig = config('database.connections.mysql');
        if (! is_array($mysqlConfig)) {
            throw new \RuntimeException('Missing mysql connection config.');
        }

        $mysqlConfig['database'] = $sourceDb;
        config(['database.connections.mysql_source' => $mysqlConfig]);

        DB::purge('mysql_source');
        DB::connection('mysql_source')->getPdo();
    }
}
