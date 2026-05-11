<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\AsksToOverwriteExisting;
use App\Console\Commands\Concerns\GeneratesModuleFiles;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleModelCommand extends BaseCommand
{
    use GeneratesModuleFiles;
    use AsksToOverwriteExisting;

    protected $signature = 'm:model-module {module} {name}
                            {--force : Overwrite if file exists}
                            {--yes : Force yes to all prompts}
                            {--migration : Also create a migration for the model}
                            {--factory : Also create a factory for the model}
                            {--seeder : Also create a seeder for the model}
                            {--create-model= : yes|no}
                            {--create-migration= : yes|no}
                            {--create-factory= : yes|no}
                            {--create-seeder= : yes|no}
                            {--create-another-migration= : yes|no when migration exists}
                            {--factory-file= : yes|no}
                            {--seeder-file= : yes|no}
                            {--skip-questions : Do not ask questions for unspecified options; use defaults}';

    protected $description = '[Deprecated] Create a module model (use m:model for app models).';

    public function handle()
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        $module = $this->studly((string) $this->argument('module'));
        if (! $this->ensureModuleExists($files, $module)) {
            $this->error("Module [$module] does not exist. Run: php artisan m:module {$module}");
            return 1;
        }

        $rawName = Str::studly((string) $this->argument('name'));
        $modelClass = Str::endsWith($rawName, 'Model') ? Str::beforeLast($rawName, 'Model') : $rawName;

        $baseClass = $module.'Model';
        $basePath = $this->moduleRoot($module)."/Models/{$baseClass}.php";

        if (! $files->exists($basePath)) {
            $contents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-model-base.stub'), [
                'NAMESPACE' => $this->moduleNamespace($module, 'Models'),
                'CLASS' => $baseClass,
            ]);
            $this->putFile($files, $basePath, $contents, true);
            $this->line("Created base: {$baseClass}");
        }

        $modelPath = $this->moduleRoot($module)."/Models/{$modelClass}.php";
        $modelAction = $this->askCreateOrOverwrite($files, 'create-model', "Create {$modelClass} model for module {$module}?", $modelPath, true);

        $createMigration = (bool) $this->option('migration');
        if (! $createMigration) {
            $createMigration = $this->askYesNo('create-migration', "Create migration for {$modelClass}?", true);
        }

        $createFactory = (bool) $this->option('factory');
        if (! $createFactory) {
            $createFactory = $this->askYesNo('create-factory', "Create {$modelClass}Factory?", true);
        }

        $createSeeder = (bool) $this->option('seeder');
        if (! $createSeeder) {
            $createSeeder = $this->askYesNo('create-seeder', "Create {$modelClass}Seeder?", false);
        }

        $factoryClass = $modelClass.'Factory';
        $seederClass = $modelClass.'Seeder';

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

        $table = (string) ($this->option('table') ?: Str::snake(Str::pluralStudly($modelClass)));

        $createMigration = (bool) $this->option('migration');
        if (! $createMigration) {
            $createMigration = $this->askYesNo('create-migration', "Create migration for table '{$table}'?", true);
        }

        $factoryMethod = '';
        if ($createFactory) {
            $factoryFqcn = $this->moduleNamespace($module, 'Database\\Factories').'\\'.$factoryClass;
            $factoryMethod = "\n    protected static function newFactory()\n    {\n        return \\{$factoryFqcn}::new();\n    }\n";
        }

        $contents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-model-child.stub'), [
            'NAMESPACE' => $this->moduleNamespace($module, 'Models'),
            'CLASS' => $modelClass,
            'BASE_CLASS' => $baseClass,
            'FACTORY_METHOD' => $factoryMethod,
        ]);

        if ($modelAction === 'create' || $modelAction === 'overwrite') {
            try {
                $this->putFile($files, $modelPath, $contents, $modelAction === 'overwrite' || (bool) $this->option('force'));
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());
                return 1;
            }
            $this->info("Created: {$modelPath}");
        }

        if ($createFactory) {
            $factoryPath = $this->moduleRoot($module)."/Database/Factories/{$factoryClass}.php";
            $factoryAction = $this->askCreateOrOverwrite($files, 'factory-file', "Create {$factoryClass} for module {$module}?", $factoryPath, true);
            if ($factoryAction === 'keep' || $factoryAction === 'no') {
                // skip
            } else {
            $factoryContents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-model-factory.stub'), [
                'NAMESPACE' => $this->moduleNamespace($module, 'Database\\Factories'),
                'CLASS' => $factoryClass,
                'MODEL_FQCN' => $this->moduleNamespace($module, 'Models').'\\\\'.$modelClass,
            ]);

                try {
                    $this->putFile($files, $factoryPath, $factoryContents, $factoryAction === 'overwrite' || (bool) $this->option('force'));
                } catch (\RuntimeException $e) {
                    $this->error($e->getMessage());
                    return 1;
                }

                $this->info("Created: {$factoryPath}");
            }
        }

        if ($createSeeder) {
            $seederPath = $this->moduleRoot($module)."/Database/Seeders/{$seederClass}.php";
            $seederAction = $this->askCreateOrOverwrite($files, 'seeder-file', "Create {$seederClass} for module {$module}?", $seederPath, false);
            if ($seederAction === 'keep' || $seederAction === 'no') {
                // skip
            } else {
            $seederContents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-model-seeder.stub'), [
                'NAMESPACE' => $this->moduleNamespace($module, 'Database\\Seeders'),
                'CLASS' => $seederClass,
                'MODEL_FQCN' => $this->moduleNamespace($module, 'Models').'\\\\'.$modelClass,
            ]);

                try {
                    $this->putFile($files, $seederPath, $seederContents, $seederAction === 'overwrite' || (bool) $this->option('force'));
                } catch (\RuntimeException $e) {
                    $this->error($e->getMessage());
                    return 1;
                }

                $this->info("Created: {$seederPath}");
            }
        }

        if ($createMigration) {
            $migrationName = 'create_'.$table.'_table';
            $this->call('make:migration', [
                'name' => $migrationName,
                '--create' => $table,
            ]);
        }

        return 0;
    }
}
