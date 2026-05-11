<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\GeneratesModuleFiles;
use App\Console\Commands\Concerns\GeneratesModuleTests;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleQueryCommand extends BaseCommand
{
    use GeneratesModuleFiles;
    use GeneratesModuleTests;

    protected $signature = 'm:query {module} {name}
                            {--force : Overwrite if file exists}
                            {--with-criteria : Also create corresponding Criteria class (e.g. UsersQuery -> UsersCriteria)}
                            {--create-criteria= : yes|no}
                            {--yes : Force yes for prompt steps}
                            {--skip-questions : Do not ask questions for unspecified options; use defaults}';

    protected $description = 'Create a query in a module that extends the module base query (e.g. UsersQuery extends AdminQuery).';

    public function handle()
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        $module = $this->studly((string) $this->argument('module'));
        if (! $this->ensureModuleExists($files, $module)) {
            $this->error("Module [$module] does not exist. Run: php artisan m:module {$module}");
            return 1;
        }

        $name = $this->studlyWithSuffix((string) $this->argument('name'), 'Query');

        $baseClass = $module.'Query';
        $basePath = $this->moduleRoot($module)."/Http/Queries/{$baseClass}.php";
        if (! $files->exists($basePath)) {
            $contents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-query.stub'), [
                'MODULE_NAMESPACE' => config('modules.namespace', 'Modules'),
                'STUDLY_NAME' => $module,
            ]);
            $this->putFile($files, $basePath, $contents, true);
            $this->line("Created base: {$baseClass}");
        }

        $targetPath = $this->moduleRoot($module)."/Http/Queries/{$name}.php";
        $contents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-query-child.stub'), [
            'NAMESPACE' => $this->moduleNamespace($module, 'Http\\Queries'),
            'CLASS' => $name,
            'BASE_CLASS' => $baseClass,
        ]);

        try {
            $this->putFile($files, $targetPath, $contents, (bool) $this->option('force'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return 1;
        }

        $this->info("Created: {$targetPath}");
        $this->ensureGeneratedClassTest($files, $module, 'query', $name, (bool) $this->option('force'));

        $criteriaBase = Str::endsWith($name, 'Query') ? Str::beforeLast($name, 'Query') : $name;
        $criteriaName = $this->studlyWithSuffix($criteriaBase, 'Criteria');

        $shouldCreateCriteria = (bool) $this->option('with-criteria');
        if (! $shouldCreateCriteria) {
            $shouldCreateCriteria = $this->askYesNo('create-criteria', "Create {$criteriaName} for module {$module}?", true);
        }

        if ($shouldCreateCriteria) {
            $args = ['module' => $module, 'name' => $criteriaName, '--yes' => $this->shouldForceYes()];
            if ((bool) $this->option('force')) {
                $args['--force'] = true;
            }
            $this->call('m:criteria', $args);
        }

        return 0;
    }
}
