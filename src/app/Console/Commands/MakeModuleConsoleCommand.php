<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\GeneratesModuleFiles;
use App\Console\Commands\Concerns\GeneratesModuleTests;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleConsoleCommand extends BaseCommand
{
    use GeneratesModuleFiles;
    use GeneratesModuleTests;

    protected $signature = 'm:commands {module} {name}
                            {--command= : Artisan signature (default: module-prefix + kebab class name)}
                            {--description= : Command description}
                            {--force : Overwrite if file exists}';

    protected $description = 'Create a console command in a module that extends the module base command (e.g. SchedulerTick extends AdminCommand).';

    public function handle(): int
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        $module = $this->studly((string) $this->argument('module'));
        if (! $this->ensureModuleExists($files, $module)) {
            $this->error("Module [$module] does not exist. Run: php artisan m:module {$module}");

            return 1;
        }

        $name = $this->resolveCommandClassName((string) $this->argument('name'), $module);
        if ($name === $module.'Command') {
            $this->error("Use m:module to create the abstract base [{$name}].");

            return 1;
        }

        $baseClass = $module.'Command';
        $basePath = $this->moduleRoot($module)."/Console/Commands/{$baseClass}.php";
        if (! $files->exists($basePath)) {
            $contents = $this->renderStub($files, base_path('stubs/modules/command.stub'), [
                'NAMESPACE' => $this->moduleNamespace($module, 'Console\\Commands'),
                'CLASS' => $baseClass,
                'COMMAND_NAME' => Str::lower($module).':run',
            ]);
            $this->putFile($files, $basePath, $contents, true);
            $this->line("Created base: {$baseClass}");
        }

        $signature = trim((string) $this->option('command'));
        if ($signature === '') {
            $signature = Str::lower($module).':'.Str::kebab($name);
        }

        $description = trim((string) $this->option('description'));
        if ($description === '') {
            $description = 'Module command: '.$name;
        }

        $targetPath = $this->moduleRoot($module)."/Console/Commands/{$name}.php";
        $contents = $this->renderStub($files, base_path('stubs/modules/scaffold/module-command-child.stub'), [
            'NAMESPACE' => $this->moduleNamespace($module, 'Console\\Commands'),
            'CLASS' => $name,
            'BASE_CLASS' => $baseClass,
            'SIGNATURE' => $signature,
            'DESCRIPTION' => $description,
        ]);

        try {
            $this->putFile($files, $targetPath, $contents, (bool) $this->option('force'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return 1;
        }

        $this->info("Created: {$targetPath}");
        $this->ensureGeneratedClassTest($files, $module, 'command', $name, (bool) $this->option('force'));

        return 0;
    }

    private function resolveCommandClassName(string $name, string $module): string
    {
        $name = trim($name);
        $baseSuffix = $module.'Command';

        if (Str::endsWith($name, 'Command') && $name !== $baseSuffix) {
            return $this->studly(substr($name, 0, -strlen('Command')));
        }

        return $this->studly($name);
    }
}
