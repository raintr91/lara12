<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class RemoveGroupModelCommand extends Command
{
    protected $signature = 'rm:group-model {name : Group model name (StudlyCase)}';
    protected $description = 'Remove model group folders.';

    public function handle(): int
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        $group = trim((string) $this->argument('name'), '/');
        $migrationGroup = Str::snake($group);

        $paths = [
            app_path("Models/{$group}"),
            base_path("tests/Unit/Models/{$group}"),
            database_path("factories/{$group}"),
            database_path("seeders/{$group}"),
            database_path("migrations/{$migrationGroup}"),
        ];

        foreach ($paths as $path) {
            $this->deleteDirectory($files, $path);
        }

        return self::SUCCESS;
    }

    private function deleteDirectory(Filesystem $files, string $path): void
    {
        if (! $files->isDirectory($path)) {
            $this->warn("Not found: {$path}");
            return;
        }

        $files->deleteDirectory($path);

        $this->info("Deleted: {$path}");
    }
}
