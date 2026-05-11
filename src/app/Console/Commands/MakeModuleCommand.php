<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'm:module {name : Module name (StudlyCase)}
                            {--force : Overwrite if module exists}
                            {--disabled : Create module but keep disabled}
                            {--plain : Create a plain module}
                            {--api : Create an API-only module}
                            {--with-web : Also keep web routes (Routes/web.php)}';

    protected $description = 'Create a new module and generate prefixed abstract base classes (AdminController, AdminMiddleware, ...) extending app base classes.';

    public function handle(): int
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        $name = Str::studly((string) $this->argument('name'));
        $lower = Str::lower($name);

        $this->info("Creating module [$name]...");

        $args = [
            'name' => [$name],
            '--force' => (bool) $this->option('force'),
            '--disabled' => (bool) $this->option('disabled'),
        ];

        if ($this->option('plain')) {
            $args['--plain'] = true;
        }

        $withWeb = (bool) $this->option('with-web');
        $apiOnly = (bool) $this->option('api');

        // Default to API-only modules (no Routes/web.php) unless --with-web is explicitly provided.
        if (! $withWeb) {
            $args['--api'] = true;
        } elseif ($apiOnly) {
            // if user explicitly asks --api, respect it even if --with-web was provided
            $args['--api'] = true;
        }

        $code = $this->call('module:make', $args);
        if ($code !== 0) {
            return $code;
        }

        $this->ensureModuleTestDirectories($files, $name);
        $this->removeModuleExampleTests($files, $name);
        $this->generateInitialModuleRouteTests($files, $name);

        // API-only preference: remove Routes/web.php if it exists and make RouteServiceProvider resilient.
        if (! $withWeb) {
            $this->removeWebRoutesIfPresent($files, $name);
            $this->guardWebRoutesMapping($files, $name);
        }

        // Register module provider path in ModuleProvider::boot() and ::register()
            $providerFQN = 'Modules\\'.$name.'\\Providers\\'.$name.'ServiceProvider';
            $providerLine = 'app()->register(\\'.$providerFQN.'::class)';
            $this->insertProviderPath($files, $providerLine, '//:end-boot');
            $this->insertProviderPath($files, $providerLine, '//:end-register');
            $this->ensurePhpUnitTestsuite($files, $name);

        // Generate prefixed module classes using custom stubs (stubs/modules/*.stub)
        $this->call('module:make-controller', [
            'controller' => $name,
            'module' => $name,
            '--plain' => true,
        ]);

        $this->call('module:make-middleware', [
            'name' => $name.'Middleware',
            'module' => $name,
        ]);

        $this->call('module:make-request', [
            'name' => $name.'Request',
            'module' => $name,
        ]);

        $this->call('module:make-resource', [
            'name' => $name.'Resource',
            'module' => $name,
        ]);

        $this->generateFromStub(
            $files,
            base_path('stubs/modules/scaffold/module-criteria-base.stub'),
            base_path('Modules/'.$name.'/Http/Queries/Criteria/'.$name.'Criteria.php'),
            [
                'MODULE_NAMESPACE' => config('modules.namespace', 'Modules'),
                'STUDLY_NAME' => $name,
            ]
        );

        $this->call('module:make-job', [
            'name' => $name.'Job',
            'module' => $name,
        ]);

        $this->call('module:make-command', [
            'name' => $name.'Command',
            'module' => $name,
            '--command' => $lower.':run',
        ]);

        // Actions / Queries are custom conventions (no built-in nwidart generators)
        $moduleRoot = base_path('Modules/'.$name);

        $this->generateFromStub(
            $files,
            base_path('stubs/modules/scaffold/module-action.stub'),
            $moduleRoot.'/Http/Actions/'.$name.'Action.php',
            [
                'MODULE_NAMESPACE' => config('modules.namespace', 'Modules'),
                'STUDLY_NAME' => $name,
            ]
        );

        $this->generateFromStub(
            $files,
            base_path('stubs/modules/scaffold/module-query.stub'),
            $moduleRoot.'/Http/Queries/'.$name.'Query.php',
            [
                'MODULE_NAMESPACE' => config('modules.namespace', 'Modules'),
                'STUDLY_NAME' => $name,
            ]
        );

        $this->newLine();
        $this->info("Module [$name] ready.");
        $this->line("Generated: {$name}Controller, {$name}Middleware, {$name}Request, {$name}Resource, {$name}Action, {$name}Query, {$name}Job, {$name}Command");

        return 0;
    }

    private function removeWebRoutesIfPresent(Filesystem $files, string $module): void
    {
        $webRoutesPath = base_path('Modules/'.$module.'/Routes/web.php');
        if ($files->exists($webRoutesPath)) {
            $files->delete($webRoutesPath);
            $this->line("Removed: {$webRoutesPath}");
        }
    }

    private function guardWebRoutesMapping(Filesystem $files, string $module): void
    {
        $providerPath = base_path('Modules/'.$module.'/Providers/RouteServiceProvider.php');
        if (! $files->exists($providerPath)) {
            return;
        }

        $contents = $files->get($providerPath);
        if (str_contains($contents, "is_file(module_path")) {
            return;
        }

        // Guard the web route mapping so missing Routes/web.php won't break boot.
        $search = "\$this->mapApiRoutes();\n        \$this->mapWebRoutes();";
        $replace = "\$this->mapApiRoutes();\n\n        if (is_file(module_path(\$this->name, '/Routes/web.php'))) {\n            \$this->mapWebRoutes();\n        }";

        if (str_contains($contents, $search)) {
            $updated = str_replace($search, $replace, $contents);
            $files->put($providerPath, $updated);
            $this->line("Updated: {$providerPath} (guard web routes mapping)");
        }
    }


    private function insertProviderPath(Filesystem $files, string $lineToInsert, string $marker): void
    {
        $moduleProviderPath = base_path('app/Providers/ModuleProvider.php');
        if (! $files->exists($moduleProviderPath)) {
            $this->warn("ModuleProvider.php not found: {$moduleProviderPath}");
            return;
        }
        $contents = $files->get($moduleProviderPath);
        // Insert the given line before the marker, regardless of indent
        $lines = explode("\n", $contents);
        $newLines = [];
        $inserted = false;

        $lineToInsert = rtrim($lineToInsert);
        if ($lineToInsert !== '' && !str_ends_with($lineToInsert, ';')) {
            $lineToInsert .= ';';
        }

        foreach ($lines as $line) {
            // Insert before the marker line
            if (strpos(trim($line), $marker) === 0 && !$inserted) {
                // Only treat as duplicate if the same line is already right above this marker
                // (so having it in boot() won't block inserting it into register()).
                $already = false;
                for ($idx = count($newLines) - 1; $idx >= 0; $idx--) {
                    $prev = trim($newLines[$idx]);
                    if ($prev === '') {
                        continue;
                    }
                    $already = ($prev === trim($lineToInsert));
                    break;
                }

                if (! $already && $lineToInsert !== '') {
                    $indent = preg_replace('/[^ \t].*/', '', $line);
                    $newLines[] = $indent . $lineToInsert;
                }
                $inserted = true;
            }
            $newLines[] = $line;
        }
        $updated = implode("\n", $newLines);
        if ($updated !== $contents) {
            $files->put($moduleProviderPath, $updated);
            $this->line("Registered provider in ModuleProvider ({$marker}): {$lineToInsert}");
        }
    }

    private function generateFromStub(Filesystem $files, string $stubPath, string $targetPath, array $replacements): void
    {
        if (! $files->exists($stubPath)) {
            $this->warn("Stub not found: {$stubPath}");
            return;
        }

        $contents = $files->get($stubPath);
        foreach ($replacements as $key => $value) {
            $contents = str_replace('$'.strtoupper($key).'$', $value, $contents);
        }

        $dir = dirname($targetPath);
        if (! $files->isDirectory($dir)) {
            $files->makeDirectory($dir, 0775, true);
        }

        $files->put($targetPath, $contents);
        $this->components->task("Generating file {$targetPath}", fn () => true);
    }

    private function ensurePhpUnitTestsuite(Filesystem $files, string $module): void
    {
        $phpunitPath = base_path('phpunit.xml');
        if (! $files->exists($phpunitPath)) {
            $this->warn("phpunit.xml not found: {$phpunitPath}");
            return;
        }

        $contents = $files->get($phpunitPath);
        $suiteName = 'Module'.$module;

        // Add testsuite entry if not already present
        if (! str_contains($contents, 'testsuite name="'.$suiteName.'"')) {
            $suiteXml = "\n        <testsuite name=\"{$suiteName}\">\n            <directory suffix=\"Test.php\">Modules/{$module}/Tests</directory>\n        </testsuite>";
            $marker = "\n    </testsuites>";

            if (! str_contains($contents, $marker)) {
                $this->warn('Cannot insert testsuite into phpunit.xml (missing </testsuites>).');
                return;
            }

            $contents = str_replace($marker, $suiteXml.$marker, $contents);
            $this->line("Registered PHPUnit testsuite: {$suiteName}");
        }

        // Add coverage exclude entries for non-executable resource files
        $langExclude  = "<directory suffix=\".php\">Modules/{$module}/Resources/Lang</directory>";
        $viewsExclude = "<directory suffix=\".php\">Modules/{$module}/Resources/Views</directory>";
        $excludeMarker = "\n        </exclude>";

        if (! str_contains($contents, $langExclude)) {
            if (! str_contains($contents, $excludeMarker)) {
                $this->warn('Cannot insert coverage excludes into phpunit.xml (missing </exclude>).');
            } else {
                $excludeXml = "\n            {$langExclude}\n            {$viewsExclude}";
                $contents = str_replace($excludeMarker, $excludeXml.$excludeMarker, $contents);
                $this->line("Added coverage excludes for Modules/{$module}/Resources/(Lang|Views)");
            }
        }

        $files->put($phpunitPath, $contents);
    }

    private function ensureModuleTestDirectories(Filesystem $files, string $module): void
    {
        $moduleRoot = base_path('Modules/'.$module);
        $unitDir = $moduleRoot.'/Tests/Unit';
        $featureDir = $moduleRoot.'/Tests/Feature';

        if (! $files->isDirectory($unitDir)) {
            $files->makeDirectory($unitDir, 0775, true);
            $this->line("Created: {$unitDir}");
        }

        if (! $files->isDirectory($featureDir)) {
            $files->makeDirectory($featureDir, 0775, true);
            $this->line("Created: {$featureDir}");
        }
    }

    private function removeModuleExampleTests(Filesystem $files, string $module): void
    {
        $paths = [
            base_path("Modules/{$module}/Tests/Feature/ExampleTest.php"),
            base_path("Modules/{$module}/Tests/Unit/ExampleTest.php"),
        ];

        foreach ($paths as $path) {
            if ($files->exists($path)) {
                $files->delete($path);
                $this->line("Removed: {$path}");
            }
        }
    }

    private function generateInitialModuleRouteTests(Filesystem $files, string $module): void
    {
        $featureDir = base_path("Modules/{$module}/Tests/Feature");

        $replacements = [
            'MODULE_NAMESPACE' => config('modules.namespace', 'Modules'),
            'STUDLY_NAME' => $module,
            'MODULE_NAME_LOWER' => Str::lower($module),
        ];

        $this->generateFromStub(
            $files,
            base_path('stubs/modules/scaffold/test/module-route-files-test.stub'),
            $featureDir.'/ModuleRouteFilesTest.php',
            $replacements
        );

        $this->generateFromStub(
            $files,
            base_path('stubs/modules/scaffold/test/route-registration-smoke-test.stub'),
            $featureDir.'/RouteRegistrationSmokeTest.php',
            $replacements
        );
    }
}

