<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\GeneratesModuleFiles;
use App\Console\Commands\Concerns\GeneratesModuleTests;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleActionCommand extends BaseCommand
{
    use GeneratesModuleFiles;
    use GeneratesModuleTests;

    protected $signature = 'm:action {module} {name}
                            {--force : Overwrite if file exists}
                            {--model-fqn= : Fully qualified model class name (e.g. App\\Models\\Platform\\Chain)}';

    protected $description = 'Create an action in a module that extends the module base action (e.g. CreateUserAction extends AdminAction).';

    public function handle()
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        $module = $this->studly((string) $this->argument('module'));
        if (! $this->ensureModuleExists($files, $module)) {
            $this->error("Module [$module] does not exist. Run: php artisan m:module {$module}");
            return 1;
        }

        $name = $this->studlyWithSuffix((string) $this->argument('name'), 'Action');

        $baseClass = $module.'Action';
        $basePath = $this->moduleRoot($module)."/Http/Actions/{$baseClass}.php";
        if (! $files->exists($basePath)) {
            $contents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-action.stub'), [
                'MODULE_NAMESPACE' => config('modules.namespace', 'Modules'),
                'STUDLY_NAME' => $module,
            ]);
            $this->putFile($files, $basePath, $contents, true);
            $this->line("Created base: {$baseClass}");
        }


        $targetPath = $this->moduleRoot($module)."/Http/Actions/{$name}.php";

        $modelFqn = trim((string) $this->option('model-fqn'));
        if ($modelFqn) {
            $modelClass = $modelFqn;
            $shortModelClass = Str::afterLast($modelClass, '\\');
            $constructorSignature = $shortModelClass . ' $model';
            $modelAssign = '        $this->model = $model;';
        } else {
            $modelClass = $this->moduleModelClass($module, $name);
            $constructorSignature = '';
            $modelAssign = '        $this->model = null;';
        }

        $contents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-action-child.stub'), [
            'NAMESPACE' => $this->moduleNamespace($module, 'Http\\Actions'),
            'CLASS' => $name,
            'BASE_CLASS' => $baseClass,
            'MODEL_CLASS' => $modelClass,
            'MODEL_VAR' => $constructorSignature,
            'MODEL_ASSIGN' => $modelAssign,
        ]);

        try {
            $this->putFile($files, $targetPath, $contents, (bool) $this->option('force'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return 1;
        }

        $this->info("Created: {$targetPath}");
        $this->ensureGeneratedClassTest($files, $module, 'action', $name, (bool) $this->option('force'));
        return 0;
    }
}
