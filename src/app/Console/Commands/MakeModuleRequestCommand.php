<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\GeneratesModuleFiles;
use App\Console\Commands\Concerns\GeneratesModuleTests;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleRequestCommand extends BaseCommand
{
    use GeneratesModuleFiles;
    use GeneratesModuleTests;

    protected $signature = 'm:request {module} {name}
                            {--force : Overwrite if file exists}';

    protected $description = 'Create a request in a module that extends the module base request (e.g. CreateUserRequest extends AdminRequest).';

    public function handle()
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        $module = $this->studly((string) $this->argument('module'));
        if (! $this->ensureModuleExists($files, $module)) {
            $this->error("Module [$module] does not exist. Run: php artisan m:module {$module}");
            return 1;
        }

        $name = $this->studlyWithSuffix((string) $this->argument('name'), 'Request');

        $baseClass = $module.'Request';
        $basePath = $this->moduleRoot($module)."/Http/Requests/{$baseClass}.php";
        if (! $files->exists($basePath)) {
            $contents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-request-base.stub'), [
                'MODULE_NAMESPACE' => config('modules.namespace', 'Modules'),
                'STUDLY_NAME' => $module,
            ]);
            $this->putFile($files, $basePath, $contents, true);
            $this->line("Created base: {$baseClass}");
        }

        $targetPath = $this->moduleRoot($module)."/Http/Requests/{$name}.php";
        $extendsBulkDelete = Str::endsWith($name, 'BulkDeleteRequest');
        $contents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-request-child.stub'), [
            'NAMESPACE' => $this->moduleNamespace($module, 'Http\\Requests'),
            'CLASS' => $name,
            'BASE_CLASS' => $extendsBulkDelete ? 'BulkDeleteRequest' : $baseClass,
            'BASE_IMPORT' => $extendsBulkDelete ? "use App\\Http\\Requests\\BulkDeleteRequest;" : '',
        ]);

        try {
            $this->putFile($files, $targetPath, $contents, (bool) $this->option('force'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return 1;
        }

        $this->info("Created: {$targetPath}");
        $this->ensureGeneratedClassTest($files, $module, 'request', $name, (bool) $this->option('force'));
        return 0;
    }
}
