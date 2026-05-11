<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\GeneratesModuleFiles;
use App\Console\Commands\Concerns\GeneratesModuleTests;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleResourceCommand extends BaseCommand
{
    use GeneratesModuleFiles;
    use GeneratesModuleTests;

    protected $signature = 'm:resource {module} {name}
                            {--force : Overwrite if file exists}';

    protected $description = 'Create a resource in a module that extends the module base resource (e.g. UserResource extends AdminResource).';

    public function handle()
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        $module = $this->studly((string) $this->argument('module'));
        if (! $this->ensureModuleExists($files, $module)) {
            $this->error("Module [$module] does not exist. Run: php artisan m:module {$module}");
            return 1;
        }

        $name = $this->studlyWithSuffix((string) $this->argument('name'), 'Resource');

        $baseClass = $module.'Resource';
        $basePath = $this->moduleRoot($module)."/Http/Resources/{$baseClass}.php";
        if (! $files->exists($basePath)) {
            $contents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-resource-base.stub'), [
                'MODULE_NAMESPACE' => config('modules.namespace', 'Modules'),
                'STUDLY_NAME' => $module,
            ]);
            $this->putFile($files, $basePath, $contents, true);
            $this->line("Created base: {$baseClass}");
        }

        $targetPath = $this->moduleRoot($module)."/Http/Resources/{$name}.php";
        $contents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-resource-child.stub'), [
            'NAMESPACE' => $this->moduleNamespace($module, 'Http\\Resources'),
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
        $this->ensureGeneratedClassTest($files, $module, 'resource', $name, (bool) $this->option('force'));
        return 0;
    }
}
