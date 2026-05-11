<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\GeneratesModuleFiles;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleMiddlewareCommand extends BaseCommand
{
    use GeneratesModuleFiles;

    protected $signature = 'm:middleware {module} {name}
                            {--force : Overwrite if file exists}
                            {--yes : Force yes/overwrite for all prompt steps}';

    protected $description = 'Create a middleware in a module that extends the module base middleware (e.g. AuditMiddleware extends AdminMiddleware).';

    public function handle()
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];
        $force = (bool) $this->option('force') || $this->shouldForceYes();

        $module = $this->studly((string) $this->argument('module'));
        if (! $this->ensureModuleExists($files, $module)) {
            $this->error("Module [$module] does not exist. Run: php artisan m:module {$module}");
            return 1;
        }

        $name = $this->studlyWithSuffix((string) $this->argument('name'), 'Middleware');

        $baseClass = $module.'Middleware';

        $basePath = $this->moduleRoot($module)."/Http/Middleware/{$baseClass}.php";
        if (! $files->exists($basePath)) {
            $contents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-middleware-base.stub'), [
                'MODULE_NAMESPACE' => config('modules.namespace', 'Modules'),
                'STUDLY_NAME' => $module,
            ]);
            $this->putFile($files, $basePath, $contents, true);
            $this->line("Created base: {$baseClass}");
        }

        $targetPath = $this->moduleRoot($module)."/Http/Middleware/{$name}.php";

        $contents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-middleware-child.stub'), [
            'NAMESPACE' => $this->moduleNamespace($module, 'Http\\Middleware'),
            'CLASS' => $name,
            'BASE_CLASS' => $baseClass,
        ]);

        try {
            $this->putFile($files, $targetPath, $contents, $force);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return 1;
        }

        $this->info("Created: {$targetPath}");
        return 0;
    }
}
