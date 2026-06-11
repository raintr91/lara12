<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\GeneratesModuleFiles;
use App\Console\Commands\Concerns\GeneratesModuleTests;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleJobCommand extends BaseCommand
{
    use GeneratesModuleFiles;
    use GeneratesModuleTests;

    protected $signature = 'm:job {module} {name}
                            {--force : Overwrite if file exists}';

    protected $description = 'Create a queue job in a module that extends the module base job (e.g. RunDueItem extends AdminJob).';

    public function handle(): int
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        $module = $this->studly((string) $this->argument('module'));
        if (! $this->ensureModuleExists($files, $module)) {
            $this->error("Module [$module] does not exist. Run: php artisan m:module {$module}");

            return 1;
        }

        $name = $this->resolveJobClassName((string) $this->argument('name'), $module);
        if ($name === $module.'Job') {
            $this->error("Use m:module to create the abstract base [{$name}].");

            return 1;
        }

        $baseClass = $module.'Job';
        $basePath = $this->moduleRoot($module)."/Jobs/{$baseClass}.php";
        if (! $files->exists($basePath)) {
            $contents = $this->renderStub($files, base_path('stubs/modules/job.stub'), [
                'NAMESPACE' => $this->moduleNamespace($module, 'Jobs'),
                'CLASS' => $baseClass,
            ]);
            $this->putFile($files, $basePath, $contents, true);
            $this->line("Created base: {$baseClass}");
        }

        $targetPath = $this->moduleRoot($module)."/Jobs/{$name}.php";
        $contents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-job-child.stub'), [
            'NAMESPACE' => $this->moduleNamespace($module, 'Jobs'),
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
        $this->ensureGeneratedClassTest($files, $module, 'job', $name, (bool) $this->option('force'));

        return 0;
    }

    private function resolveJobClassName(string $name, string $module): string
    {
        $name = trim($name);
        $baseSuffix = $module.'Job';

        if (Str::endsWith($name, 'Job') && $name !== $baseSuffix) {
            return $this->studly(substr($name, 0, -strlen('Job')));
        }

        return $this->studly($name);
    }
}
