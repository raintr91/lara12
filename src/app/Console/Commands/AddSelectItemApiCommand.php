<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\AsksToOverwriteExisting;
use App\Console\Commands\Concerns\GeneratesModuleFiles;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class AddSelectItemApiCommand extends BaseCommand
{
    use GeneratesModuleFiles;
    use AsksToOverwriteExisting;

    protected $signature = 'add:select-item {module : Module name (StudlyCase)} {controller : Controller name (StudlyCase, no Controller suffix)}
                            {--force : Overwrite generated request/resource if they exist}
                            {--yes : Force yes/overwrite for all prompt steps}
                            {--skip-questions : Do not ask questions for unspecified options; use defaults}';

    protected $description = 'Add select-items API scaffolding (request/resource + controller/query wiring) to an existing module controller.';

    public function handle()
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        $module = Str::studly((string) $this->argument('module'));
        if (! $this->ensureModuleExists($files, $module)) {
            $this->error("Module [$module] does not exist. Run: php artisan m:module {$module}");
            return 1;
        }

        $controllerBase = $this->studly((string) $this->argument('controller'));
        $controllerClass = $this->studlyWithSuffix($controllerBase, 'Controller');
        $name = Str::beforeLast($controllerClass, 'Controller');

        $moduleRoot = $this->moduleRoot($module);

        $controllerPath = $moduleRoot . "/Http/Controllers/{$controllerClass}.php";
        if (! $files->exists($controllerPath)) {
            $this->error("Controller not found: {$controllerPath}. Create it first with: php artisan m:controller {$module} {$name}");
            return 1;
        }

        $queryClass = $this->studlyWithSuffix($name, 'Query');
        $queryPath = $moduleRoot . "/Http/Queries/{$queryClass}.php";

        if (! $files->exists($queryPath)) {
            $shouldCreateQuery = $this->askYesNo('yes', "Query not found. Create {$queryClass} for module {$module}?", true);

            if (! $shouldCreateQuery) {
                $this->warn('Select-items needs a Query class. Aborting.');
                return 1;
            }

            $this->call('m:query', [
                'module' => $module,
                'name' => $queryClass,
                '--yes' => $this->shouldForceYes(),
            ]);
        }

        $selectRequestClass = $this->studlyWithSuffix($name . 'SelectItem', 'Request');
        $selectRequestPath = $moduleRoot . "/Http/Requests/{$selectRequestClass}.php";

        $selectResourceClass = $this->studlyWithSuffix($name . 'SelectItem', 'Resource');
        $selectResourcePath = $moduleRoot . "/Http/Resources/{$selectResourceClass}.php";

        $modelFqn = $this->guessModelFqn($files, $name);

        $force = (bool) $this->option('force');

        $this->createSelectItemRequest($files, $module, $selectRequestClass, $selectRequestPath, $modelFqn, $force);
        $this->createSelectItemResource($files, $module, $selectResourceClass, $selectResourcePath, $force);

        $this->patchQuery($files, $queryPath);
        $this->patchController($files, $controllerPath, $module, $queryClass, $selectRequestClass, $selectResourceClass);

        $this->patchRoutes($files, $moduleRoot . '/Routes/api.php', $module, $name, $controllerClass);

        $this->info("Select-items API ready for {$module}::{$controllerClass}.");
        return 0;
    }

    private function guessModelFqn(Filesystem $files, string $name): string
    {
        $candidates = [
            'App\\Models\\' . $name,
            'App\\' . $name,
        ];

        foreach ($candidates as $fqn) {
            if (class_exists($fqn)) {
                return $fqn . '::class';
            }
        }

        $modelPath = base_path('app/Models/' . $name . '.php');
        if ($files->exists($modelPath)) {
            return 'App\\Models\\' . $name . '::class';
        }

        return "''";
    }

    private function createSelectItemRequest(
        Filesystem $files,
        string $module,
        string $class,
        string $path,
        string $modelFqn,
        bool $force
    ): void {
        $stubPath = base_path('stubs/modules/scaffold/module-select-item-request.stub');

        $choice = $force ? ($files->exists($path) ? 'overwrite' : 'create')
            : $this->confirmCreateOrOverwrite($files, "Create {$class} for module {$module}?", $path, true);

        if (!in_array($choice, ['create', 'overwrite'], true)) {
            return;
        }

        $contents = $this->renderStub($files, $stubPath, [
            'namespace' => $this->moduleNamespace($module, 'Http\\Requests'),
            'class' => $class,
            'model_fqn' => $modelFqn,
        ]);

        $this->putFile($files, $path, $contents, $choice === 'overwrite');
        $this->line("Generated: {$path}");

        if ($modelFqn === "''") {
            $this->warn("{$class}: please set protected \$model to your Eloquent model class.");
        }
    }

    private function createSelectItemResource(
        Filesystem $files,
        string $module,
        string $class,
        string $path,
        bool $force
    ): void {
        $stubPath = base_path('stubs/modules/scaffold/module-select-item-resource.stub');

        $choice = $force ? ($files->exists($path) ? 'overwrite' : 'create')
            : $this->confirmCreateOrOverwrite($files, "Create {$class} for module {$module}?", $path, true);

        if (!in_array($choice, ['create', 'overwrite'], true)) {
            return;
        }

        $contents = $this->renderStub($files, $stubPath, [
            'namespace' => $this->moduleNamespace($module, 'Http\\Resources'),
            'class' => $class,
        ]);

        $this->putFile($files, $path, $contents, $choice === 'overwrite');
        $this->line("Generated: {$path}");
    }

    private function patchQuery(Filesystem $files, string $path): void
    {
        $contents = $files->get($path);

        if (!str_contains($contents, 'SelectItemQueryTrait')) {
            // Add import after namespace block.
            $contents = preg_replace(
                '/^namespace\s+[^;]+;\n\n/m',
                "$0use App\\Http\\Queries\\Traits\\SelectItemQueryTrait;\n",
                $contents,
                1
            ) ?? $contents;

            // Add trait usage right after class opening.
            $contents = preg_replace(
                '/class\s+\w+\s+extends\s+\w+\s*\{\n/m',
                "$0    use SelectItemQueryTrait;\n\n",
                $contents,
                1
            ) ?? $contents;

            $files->put($path, $contents);
            $this->line("Patched: {$path} (SelectItemQueryTrait)");
        }
    }

    private function patchController(
        Filesystem $files,
        string $path,
        string $module,
        string $queryClass,
        string $selectRequestClass,
        string $selectResourceClass
    ): void {
        $contents = $files->get($path);

        $imports = [
            'App\\Http\\Controllers\\Traits\\SelectItemControllerTrait',
            "Modules\\{$module}\\Http\\Queries\\{$queryClass}",
            "Modules\\{$module}\\Http\\Requests\\{$selectRequestClass}",
            "Modules\\{$module}\\Http\\Resources\\{$selectResourceClass}",
        ];

        foreach ($imports as $import) {
            if (!str_contains($contents, "use {$import};")) {
                $contents = preg_replace(
                    '/^namespace\s+[^;]+;\n\n/m',
                    "$0use {$import};\n",
                    $contents,
                    1
                ) ?? $contents;
            }
        }

        $hasTraitUse = (bool) preg_match('/\buse\s+SelectItemControllerTrait\b/', $contents);
        if (! $hasTraitUse) {
            // Insert trait usage inside class.
            $contents = preg_replace(
                '/class\s+\w+\s+extends\s+\w+\s*\{\n/m',
                "$0    use SelectItemControllerTrait {\n        getListSelect as protected traitGetListSelect;\n    }\n\n",
                $contents,
                1
            ) ?? $contents;
        }

        if (!str_contains($contents, 'function selectItemQueryClass')) {
            $methods = "\n    public function getListSelect({$selectRequestClass} \$request)\n    {\n        return \$this->traitGetListSelect(\$request);\n    }\n\n    protected function selectItemQueryClass(): string\n    {\n        return {$queryClass}::class;\n    }\n\n    protected function selectItemResourceClass(): string\n    {\n        return {$selectResourceClass}::class;\n    }\n";

            $contents = preg_replace('/\n}\s*$/', $methods . "\n}\n", $contents, 1) ?? $contents;
        }

        $files->put($path, $contents);
        $this->line("Patched: {$path} (SelectItemControllerTrait)");
    }

    private function patchRoutes(
        Filesystem $files,
        string $apiRoutesPath,
        string $module,
        string $name,
        string $controllerClass
    ): void {
        if (! $files->exists($apiRoutesPath)) {
            return;
        }

        $contents = $files->get($apiRoutesPath);

        $controllerImport = "Modules\\{$module}\\Http\\Controllers\\{$controllerClass}";
        if (!str_contains($contents, "use {$controllerImport};")) {
            $contents = preg_replace(
                '/^<\?php\n\n/m',
                "<?php\n\nuse {$controllerImport};\n\n",
                $contents,
                1
            ) ?? $contents;
        }

        $prefix = Str::plural(Str::snake($name));
        $needle = "prefix('{$prefix}')";

        $alreadyWired = str_contains($contents, "[{$controllerClass}::class, 'getListSelect']");
        if ($alreadyWired) {
            // Already wired.
            if ($contents !== $files->get($apiRoutesPath)) {
                $files->put($apiRoutesPath, $contents);
                $this->line("Patched: {$apiRoutesPath} (controller import)");
            }
            return;
        }

        $routeLine = "            Route::post('select-items', [{$controllerClass}::class, 'getListSelect']);\n";

        if (str_contains($contents, $needle)) {
            // Insert right after the existing search route if present, else after group open.
            $contents2 = preg_replace(
                "/(Route::post\('search', \[{$controllerClass}::class, 'search'\]\);\n)/",
                "$1{$routeLine}",
                $contents,
                1
            );

            if ($contents2 === null || $contents2 === $contents) {
                $contents2 = preg_replace(
                    "/(prefix\('{$prefix}'\)->group\(function \(\) \{\n)/",
                    "$1{$routeLine}",
                    $contents,
                    1
                ) ?? $contents;
            }

            if ($contents2 !== $contents) {
                $files->put($apiRoutesPath, $contents2);
                $this->line("Patched: {$apiRoutesPath} (select-items route)");
            } else {
                if ($contents !== $files->get($apiRoutesPath)) {
                    $files->put($apiRoutesPath, $contents);
                    $this->line("Patched: {$apiRoutesPath} (controller import)");
                }
            }
            return;
        }

        // Group not found: add a minimal group under the module group.
        $block = "\n        Route::middleware('auth:sanctum')->prefix('{$prefix}')->group(function () {\n{$routeLine}        });\n";
        $contents2 = preg_replace(
            "/(require\s+__DIR__\s*\.\s*['\"]\\/auth\\.php['\"];\s*\n)/",
            "$1{$block}",
            $contents,
            1
        ) ?? $contents;

        if ($contents2 !== $contents) {
            $files->put($apiRoutesPath, $contents2);
            $this->line("Patched: {$apiRoutesPath} (added {$prefix} select-items group)");
            return;
        }

        // Fallback: if auth.php require pattern differs, append near end of module group.
        $contents3 = preg_replace('/\n\s*\}\);\s*$/', $block . "\n});\n", $contents, 1) ?? $contents;
        if ($contents3 !== $contents) {
            $files->put($apiRoutesPath, $contents3);
            $this->line("Patched: {$apiRoutesPath} (appended {$prefix} select-items group)");
        }
    }
}
