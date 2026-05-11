<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\GeneratesModuleFiles;
use App\Console\Commands\Concerns\GeneratesModuleTests;
use Illuminate\Filesystem\Filesystem;

class MakeModuleTestCommand extends BaseCommand
{
    use GeneratesModuleFiles;
    use GeneratesModuleTests;

    private string $controllerLayer = 'feature';

    protected $signature = 'm:module-test
                            {module : Module name (StudlyCase)}
                            {--type=all : controller|action|query|request|resource|all}
                            {--class= : Specific class to generate test for (e.g. UserController, CreateUserAction)}
                            {--force : Overwrite generated test file if it already exists}
                            {--include-base : Include base module classes (e.g. AdminAction, AdminQuery)}
                            {--controller-layer=feature : controller tests target layer: feature|unit}';

    protected $description = 'Generate per-class module tests by layer (controller => Feature, others => Unit).';

    public function handle(): int
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        $module = $this->studly((string) $this->argument('module'));
        if (! $this->ensureModuleExists($files, $module)) {
            $this->error("Module [{$module}] does not exist. Run: php artisan m:module {$module}");
            return 1;
        }

        $type = strtolower((string) $this->option('type'));
        $class = trim((string) ($this->option('class') ?? ''));
        $force = (bool) $this->option('force');
        $includeBase = (bool) $this->option('include-base');
        $this->controllerLayer = strtolower((string) $this->option('controller-layer'));

        if (! in_array($this->controllerLayer, ['feature', 'unit'], true)) {
            $this->error("Invalid --controller-layer [{$this->controllerLayer}]. Allowed: feature, unit");
            return 1;
        }

        $validTypes = ['controller', 'action', 'query', 'request', 'resource', 'all'];
        if (! in_array($type, $validTypes, true)) {
            $this->error("Invalid --type [{$type}]. Allowed: controller, action, query, request, resource, all");
            return 1;
        }

        if ($class !== '') {
            return $this->generateSingleClassTest($files, $module, $type, $class, $force);
        }

        $targetTypes = $type === 'all'
            ? ['controller', 'action', 'query', 'request', 'resource']
            : [$type];

        $generatedCount = 0;
        foreach ($targetTypes as $targetType) {
            $generatedCount += $this->generateLayerTests($files, $module, $targetType, $force, $includeBase);
        }

        $this->info("Done. Generated {$generatedCount} test file(s) for module [{$module}].");
        return 0;
    }

    protected function controllerTestLayer(): string
    {
        return $this->controllerLayer;
    }

    private function generateSingleClassTest(
        Filesystem $files,
        string $module,
        string $type,
        string $class,
        bool $force
    ): int {
        $resolvedType = $type;
        if ($resolvedType === 'all') {
            $resolvedType = $this->detectTypeFromClassName($class);
            if ($resolvedType === null) {
                $this->error('Cannot detect class type from --class. Please provide --type explicitly.');
                return 1;
            }
        }

        $className = $this->normalizeClassNameByType($class, $resolvedType);
        $this->ensureGeneratedClassTest($files, $module, $resolvedType, $className, $force);

        return 0;
    }

    private function generateLayerTests(
        Filesystem $files,
        string $module,
        string $type,
        bool $force,
        bool $includeBase
    ): int {
        $map = [
            'controller' => ['dir' => 'Http/Controllers', 'suffix' => 'Controller'],
            'action' => ['dir' => 'Http/Actions', 'suffix' => 'Action'],
            'query' => ['dir' => 'Http/Queries', 'suffix' => 'Query'],
            'request' => ['dir' => 'Http/Requests', 'suffix' => 'Request'],
            'resource' => ['dir' => 'Http/Resources', 'suffix' => 'Resource'],
        ];

        $dir = $this->moduleRoot($module).'/'.$map[$type]['dir'];
        if (! $files->isDirectory($dir)) {
            $this->warn("Skipped {$type}: directory not found [{$dir}]");
            return 0;
        }

        $baseClass = $module.$map[$type]['suffix'];
        $generated = 0;

        $paths = $files->glob($dir.'/*.php') ?: [];
        sort($paths);

        foreach ($paths as $path) {
            $className = pathinfo($path, PATHINFO_FILENAME);
            if (! $includeBase && $className === $baseClass) {
                continue;
            }

            $this->ensureGeneratedClassTest($files, $module, $type, $className, $force);
            $generated++;
        }

        $this->line("Generated {$generated} {$type} test(s).");

        return $generated;
    }

    private function detectTypeFromClassName(string $class): ?string
    {
        $name = $this->studly($class);

        if (str_ends_with($name, 'Controller')) {
            return 'controller';
        }

        if (str_ends_with($name, 'Action')) {
            return 'action';
        }

        if (str_ends_with($name, 'Query')) {
            return 'query';
        }

        if (str_ends_with($name, 'Request')) {
            return 'request';
        }

        if (str_ends_with($name, 'Resource')) {
            return 'resource';
        }

        return null;
    }

    private function normalizeClassNameByType(string $class, string $type): string
    {
        $suffixMap = [
            'controller' => 'Controller',
            'action' => 'Action',
            'query' => 'Query',
            'request' => 'Request',
            'resource' => 'Resource',
        ];

        return $this->studlyWithSuffix($class, $suffixMap[$type]);
    }
}
