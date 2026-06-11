<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\AsksToOverwriteExisting;
use App\Console\Commands\Concerns\GeneratesModuleFiles;
use App\Console\Commands\Concerns\ResolvesAppModelPath;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModelCommand extends BaseCommand
{
    use AsksToOverwriteExisting;
    use GeneratesModuleFiles;
    use ResolvesAppModelPath;

    protected $signature = 'm:model {name : Model class name (StudlyCase, no slashes)}
                            {path? : Subpath under App/Models (e.g. Control or Control/Sub)}
                            {--force : Overwrite if file exists}
                            {--yes : Force yes to all prompts}
                            {--create-model= : yes|no}
                            {--create-migration= : yes|no}
                            {--create-factory= : yes|no}
                            {--create-seeder= : yes|no}
                            {--create-another-migration= : yes|no when migration exists}
                            {--skip-questions : Do not ask questions for unspecified options; use defaults}';

    protected $description = 'Create an app model: m:model Chain Platform → App\\Models\\Platform\\Chain';

    public function handle(): int
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        try {
            $target = $this->resolveAppModelTarget(
                (string) $this->argument('name'),
                $this->argument('path') !== null ? (string) $this->argument('path') : null
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return 1;
        }

        $modelClass = $target['modelClass'];
        $modelPath = $target['modelPath'];
        $modelTestPath = $target['testPath'];
        $modelTestClass = $target['testClass'];

        $forceWrite = (bool) $this->option('force') || $this->shouldForceYes();

        $pathLabel = $target['pathSegments'] === []
            ? 'App\\Models'
            : $target['modelNamespace'];

        $this->line("Target: <info>{$target['modelFqcn']}</info> ({$pathLabel})");

        $modelAction = $this->askCreateOrOverwrite(
            $files,
            'create-model',
            "Create {$modelClass} model at {$modelPath}?",
            $modelPath,
            true
        );

        $createMigration = $this->askYesNo('create-migration', "Create migration for table [{$target['table']}]?", true);

        $factoryPath = $target['factoryPath'];
        $factoryAction = $this->askCreateOrOverwrite(
            $files,
            'create-factory',
            "Create {$target['factoryClass']} at {$factoryPath}?",
            $factoryPath,
            true
        );

        $seederPath = $target['seederPath'];
        $seederAction = $this->askCreateOrOverwrite(
            $files,
            'create-seeder',
            "Create {$target['seederClass']} at {$seederPath}?",
            $seederPath,
            false
        );

        if ($modelAction === 'create' || $modelAction === 'overwrite') {
            $contents = $this->renderStub($files, base_path('stubs/app/model.stub'), [
                'NAMESPACE' => $target['modelNamespace'],
                'CLASS' => $modelClass,
                'BASE_CLASS' => $target['baseClass'],
                'BASE_USE' => $target['baseUse'],
            ]);

            try {
                $this->putFile($files, $modelPath, $contents, $modelAction === 'overwrite' || $forceWrite);
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());

                return 1;
            }

            $this->info("Created: {$modelPath}");
        }

        if ($modelAction !== 'no' || $files->exists($modelPath)) {
            $this->ensureModelTestFile($files, $target, $forceWrite);
        }

        if ($createMigration) {
            $this->createMigrationForTarget($target);
        }

        if ($factoryAction === 'create' || $factoryAction === 'overwrite') {
            $contents = $this->renderStub($files, base_path('stubs/app/factory.stub'), [
                'FACTORY_NAMESPACE' => $target['factoryNamespace'],
                'CLASS' => $target['factoryClass'],
                'MODEL_FQCN' => $target['modelFqcn'],
                'MODEL_CLASS' => $modelClass,
                'FACTORY_BASE_CLASS' => $target['factoryBaseClass'],
                'FACTORY_BASE_USE' => $target['factoryBaseUse'],
            ]);

            try {
                $this->putFile($files, $factoryPath, $contents, $factoryAction === 'overwrite' || $forceWrite);
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());

                return 1;
            }

            $this->info("Created: {$factoryPath}");
        }

        if ($seederAction === 'create' || $seederAction === 'overwrite') {
            $contents = $this->renderStub($files, base_path('stubs/app/seeder.stub'), [
                'SEEDER_NAMESPACE' => $target['seederNamespace'],
                'CLASS' => $target['seederClass'],
                'MODEL_FQCN' => $target['modelFqcn'],
                'MODEL_CLASS' => $modelClass,
            ]);

            try {
                $this->putFile($files, $seederPath, $contents, $seederAction === 'overwrite' || $forceWrite);
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());

                return 1;
            }

            $this->info("Created: {$seederPath}");
        }

        return 0;
    }

    /**
     * @param  array{
     *     modelClass: string,
     *     table: string,
     *     migrationPath: string
     * }  $target
     */
    private function createMigrationForTarget(array $target): void
    {
        $migrationName = 'create_'.$target['table'].'_table';
        $migrationDir = $target['migrationPath'] === 'database/migrations'
            ? database_path('migrations')
            : database_path('migrations/'.Str::after($target['migrationPath'], 'database/migrations/'));

        $existing = glob($migrationDir.'/*_'.$migrationName.'.php') ?: [];

        $createAnother = true;
        if ($existing !== []) {
            $createAnother = $this->askYesNo(
                'create-another-migration',
                "Migration for '{$target['table']}' already exists. Create another one?",
                false
            );
        }

        if ($existing !== [] && ! $createAnother) {
            return;
        }

        $args = [
            'name' => $migrationName,
            '--create' => $target['table'],
        ];

        if ($target['migrationPath'] !== 'database/migrations') {
            $args['--path'] = $target['migrationPath'];
        }

        $this->call('make:migration', $args);
    }

    /**
     * @param  array{
     *     modelClass: string,
     *     modelFqcn: string,
     *     testNamespace: string,
     *     testPath: string,
     *     testClass: string
     * }  $target
     */
    private function ensureModelTestFile(Filesystem $files, array $target, bool $forceWrite): void
    {
        $stubPath = base_path('stubs/app/model-test.stub');
        if (! $files->exists($stubPath)) {
            $this->warn("Stub not found: {$stubPath}");

            return;
        }

        if ($files->exists($target['testPath']) && ! $forceWrite) {
            $this->info("Keeping existing model test: {$target['testPath']}");

            return;
        }

        $contents = $this->renderStub($files, $stubPath, [
            'TEST_NAMESPACE' => $target['testNamespace'],
            'CLASS' => $target['testClass'],
            'MODEL_FQCN' => $target['modelFqcn'],
            'MODEL_CLASS' => $target['modelClass'],
        ]);

        try {
            $this->putFile($files, $target['testPath'], $contents, $forceWrite);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->info("Created: {$target['testPath']}");
    }
}
