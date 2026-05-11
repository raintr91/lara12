<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\AsksToOverwriteExisting;
use App\Console\Commands\Concerns\GeneratesModuleFiles;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModelCommand extends BaseCommand
{
    use GeneratesModuleFiles;
    use AsksToOverwriteExisting;

    protected $signature = 'm:model {name}
                            {--force : Overwrite if file exists}
                            {--yes : Force yes to all prompts}
                            {--create-model= : yes|no}
                            {--create-migration= : yes|no}
                            {--create-factory= : yes|no}
                            {--create-seeder= : yes|no}
                            {--create-another-migration= : yes|no when migration exists}
                            {--skip-questions : Do not ask questions for unspecified options; use defaults}';

    protected $description = 'Interactively create an app model extending BaseModel, and optionally migration/factory/seeder.';

    public function handle()
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        $rawName = Str::studly((string) $this->argument('name'));
        $modelClass = Str::endsWith($rawName, 'Model') ? Str::beforeLast($rawName, 'Model') : $rawName;

        $modelPath = app_path("Models/{$modelClass}.php");
        $modelTestClass = $modelClass.'ModelTest';
        $modelTestPath = base_path("tests/Unit/Models/{$modelTestClass}.php");
        $forceWrite = (bool) $this->option('force') || $this->shouldForceYes();
        $modelAction = $this->askCreateOrOverwrite($files, 'create-model', "Create {$modelClass} model?", $modelPath, true);

        $createMigration = $this->askYesNo('create-migration', "Create migration for {$modelClass}?", true);
        $factoryClass = $modelClass.'Factory';
        $seederClass = $modelClass.'Seeder';

        $factoryPath = database_path("factories/{$factoryClass}.php");
        $factoryAction = $this->askCreateOrOverwrite($files, 'create-factory', "Create {$factoryClass}?", $factoryPath, true);

        $seederPath = database_path("seeders/{$seederClass}.php");
        $seederAction = $this->askCreateOrOverwrite($files, 'create-seeder', "Create {$seederClass}?", $seederPath, false);

        if ($modelAction === 'create' || $modelAction === 'overwrite') {
            $contents = $this->renderStub($files, base_path('stubs/app/model.stub'), [
                'CLASS' => $modelClass,
            ]);

            try {
                $this->putFile($files, $modelPath, $contents, $modelAction === 'overwrite' || (bool) $this->option('force'));
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());
                return 1;
            }

            $this->info("Created: {$modelPath}");
        }

        // Enforce one independent test class per app model under tests/Unit/Models.
        if ($modelAction !== 'no' || $files->exists($modelPath)) {
            $this->ensureModelTestFile($files, $modelClass, $modelTestClass, $modelTestPath, $forceWrite);
        }

        if ($createMigration) {
            $table = Str::snake(Str::pluralStudly($modelClass));
            $migrationName = "create_{$table}_table";
            $existing = glob(database_path("migrations/*_{$migrationName}.php")) ?: [];

            $createAnother = true;
            if ($existing !== []) {
                $createAnother = $this->askYesNo('create-another-migration', "Migration for '{$table}' already exists. Create another one?", false);
            }

            if ($existing === [] || $createAnother) {
                $args = [
                    'name' => $migrationName,
                    '--create' => $table,
                ];
                if ($this->shouldForceYes()) {
                    $args['--yes'] = true;
                }
                $this->call('make:migration', $args);
            }
        }

        if ($factoryAction === 'create' || $factoryAction === 'overwrite') {
            $contents = $this->renderStub($files, base_path('stubs/app/factory.stub'), [
                'CLASS' => $factoryClass,
                'MODEL_CLASS' => $modelClass,
            ]);

            try {
                $this->putFile($files, $factoryPath, $contents, $factoryAction === 'overwrite' || (bool) $this->option('force'));
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());
                return 1;
            }

            $this->info("Created: {$factoryPath}");
        }

        if ($seederAction === 'create' || $seederAction === 'overwrite') {
            $contents = $this->renderStub($files, base_path('stubs/app/seeder.stub'), [
                'CLASS' => $seederClass,
                'MODEL_CLASS' => $modelClass,
            ]);

            try {
                $this->putFile($files, $seederPath, $contents, $seederAction === 'overwrite' || (bool) $this->option('force'));
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());
                return 1;
            }

            $this->info("Created: {$seederPath}");
        }

        return 0;
    }

    private function ensureModelTestFile(
        Filesystem $files,
        string $modelClass,
        string $modelTestClass,
        string $modelTestPath,
        bool $forceWrite
    ): void {
        $stubPath = base_path('stubs/app/model-test.stub');
        if (! $files->exists($stubPath)) {
            $this->warn("Stub not found: {$stubPath}");
            return;
        }

        if ($files->exists($modelTestPath) && ! $forceWrite) {
            $this->info("Keeping existing model test: {$modelTestPath}");
            return;
        }

        $contents = $this->renderStub($files, $stubPath, [
            'CLASS' => $modelTestClass,
            'MODEL_CLASS' => $modelClass,
        ]);

        try {
            $this->putFile($files, $modelTestPath, $contents, $forceWrite);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return;
        }

        $this->info("Created: {$modelTestPath}");
    }
}
