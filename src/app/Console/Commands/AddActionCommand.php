<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\AsksToOverwriteExisting;
use App\Console\Commands\Concerns\GeneratesModuleFiles;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class AddActionCommand extends BaseCommand
{
    use GeneratesModuleFiles;
    use AsksToOverwriteExisting;

    protected $signature = 'add:action {module : Module name (StudlyCase)} {controller : Controller name (StudlyCase, no Controller suffix)} {action : create|update|delete|search|detail}
                            {--yes : Force yes/overwrite for all prompt steps}
                            {--skip-questions : Do not ask questions for unspecified options; use defaults}';

    protected $description = 'Add a single controller action wiring (trait + wrapper + route) to an existing module controller.';

    public function handle()
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        $module = Str::studly((string) $this->argument('module'));
        if (! $this->ensureModuleExists($files, $module)) {
            $this->error("Module [{$module}] does not exist. Run: php artisan m:module {$module}");
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

        $action = $this->normalizeAction((string) $this->argument('action'));
        if (! $action) {
            $this->error('Invalid action. Supported: create, update, delete, search, detail');
            return 1;
        }

        $actionClass = $this->studlyWithSuffix($name, 'Action');
        $queryClass = $this->studlyWithSuffix($name, 'Query');
        $createRequestClass = $this->studlyWithSuffix($name . 'Create', 'Request');
        $searchRequestClass = $this->studlyWithSuffix($name . 'Search', 'Request');

        $actionPath = $moduleRoot . "/Http/Actions/{$actionClass}.php";
        $queryPath = $moduleRoot . "/Http/Queries/{$queryClass}.php";
        $createRequestPath = $moduleRoot . "/Http/Requests/{$createRequestClass}.php";
        $searchRequestPath = $moduleRoot . "/Http/Requests/{$searchRequestClass}.php";

        if (in_array($action, ['create', 'update', 'delete'], true)) {
            if (! $this->ensureActionExists($files, $module, $actionClass, $actionPath)) {
                return 1;
            }
        }

        if (in_array($action, ['create', 'update'], true)) {
            if (! $this->ensureRequestExists($files, $module, $createRequestClass, $createRequestPath)) {
                return 1;
            }
        }

        if (in_array($action, ['search', 'detail'], true)) {
            if (! $this->ensureQueryExists($files, $module, $queryClass, $queryPath)) {
                return 1;
            }
        }

        if ($action === 'search') {
            if (! $this->ensureRequestExists($files, $module, $searchRequestClass, $searchRequestPath)) {
                return 1;
            }
        }

        $this->patchController(
            $files,
            $controllerPath,
            $module,
            $action,
            $actionClass,
            $queryClass,
            $createRequestClass,
            $searchRequestClass
        );

        $this->patchRoutes(
            $files,
            $moduleRoot . '/Routes/api.php',
            $module,
            $name,
            $controllerClass,
            $action
        );

        $this->info("Action [{$action}] is ready for {$module}::{$controllerClass}.");

        return 0;
    }

    private function normalizeAction(string $action): ?string
    {
        $action = Str::lower(trim($action));

        return match ($action) {
            'create' => 'create',
            'update' => 'update',
            'delete' => 'delete',
            'search', 'list' => 'search',
            'detail', 'getdetail', 'get-detail' => 'detail',
            default => null,
        };
    }

    private function ensureActionExists(Filesystem $files, string $module, string $class, string $path): bool
    {
        if ($files->exists($path)) {
            return true;
        }

        $shouldCreate = $this->askYesNo('yes', "Action not found. Create {$class} for module {$module}?", true);

        if (! $shouldCreate) {
            $this->warn('This endpoint requires an Action class. Aborting.');
            return false;
        }

        $this->call('m:action', [
            'module' => $module,
            'name' => $class,
        ]);

        return true;
    }

    private function ensureQueryExists(Filesystem $files, string $module, string $class, string $path): bool
    {
        if ($files->exists($path)) {
            return true;
        }

        $shouldCreate = $this->askYesNo('yes', "Query not found. Create {$class} for module {$module}?", true);

        if (! $shouldCreate) {
            $this->warn('This endpoint requires a Query class. Aborting.');
            return false;
        }

        $this->call('m:query', [
            'module' => $module,
            'name' => $class,
            '--yes' => $this->shouldForceYes(),
        ]);

        return true;
    }

    private function ensureRequestExists(Filesystem $files, string $module, string $class, string $path): bool
    {
        if ($files->exists($path)) {
            return true;
        }

        $shouldCreate = $this->askYesNo('yes', "Request not found. Create {$class} for module {$module}?", true);

        if (! $shouldCreate) {
            $this->warn('This endpoint requires a Request class. Aborting.');
            return false;
        }

        $this->call('m:request', [
            'module' => $module,
            'name' => $class,
        ]);

        return true;
    }

    private function patchController(
        Filesystem $files,
        string $path,
        string $module,
        string $action,
        string $actionClass,
        string $queryClass,
        string $createRequestClass,
        string $searchRequestClass
    ): void {
        $contents = $files->get($path);

        $traitImport = match ($action) {
            'create' => 'App\\Http\\Controllers\\Traits\\EntryCreateTrait',
            'update' => 'App\\Http\\Controllers\\Traits\\EntryUpdateTrait',
            'delete' => 'App\\Http\\Controllers\\Traits\\EntryDeleteTrait',
            'search' => 'App\\Http\\Controllers\\Traits\\EntrySearchTrait',
            'detail' => 'App\\Http\\Controllers\\Traits\\EntryDetailTrait',
        };

        $extraImports = [$traitImport];
        if (in_array($action, ['create', 'update', 'delete'], true)) {
            $extraImports[] = "Modules\\{$module}\\Http\\Actions\\{$actionClass}";
        }
        if (in_array($action, ['search', 'detail'], true)) {
            $extraImports[] = "Modules\\{$module}\\Http\\Queries\\{$queryClass}";
        }
        if (in_array($action, ['create', 'update'], true)) {
            $extraImports[] = "Modules\\{$module}\\Http\\Requests\\{$createRequestClass}";
        }
        if ($action === 'search') {
            $extraImports[] = "Modules\\{$module}\\Http\\Requests\\{$searchRequestClass}";
        }

        foreach (array_values(array_unique($extraImports)) as $import) {
            if (! str_contains($contents, "use {$import};")) {
                $contents = preg_replace(
                    '/^namespace\s+[^;]+;\n\n/m',
                    "$0use {$import};\n",
                    $contents,
                    1
                ) ?? $contents;
            }
        }

        [$traitName, $traitAlias, $traitMethod] = match ($action) {
            'create' => ['EntryCreateTrait', 'traitCreate', 'create'],
            'update' => ['EntryUpdateTrait', 'traitUpdate', 'update'],
            'delete' => ['EntryDeleteTrait', 'traitDelete', 'delete'],
            'search' => ['EntrySearchTrait', 'traitSearch', 'search'],
            'detail' => ['EntryDetailTrait', 'traitGetDetail', 'getDetail'],
        };

        if (! preg_match('/\buse\s+' . preg_quote($traitName, '/') . '\b/', $contents)) {
            $traitBlock = "    use {$traitName} {\n        {$traitMethod} as protected {$traitAlias};\n    }\n\n";
            $contents = preg_replace(
                '/class\s+\w+\s+extends\s+\w+\s*\{\n/m',
                "$0{$traitBlock}",
                $contents,
                1
            ) ?? $contents;
        }

        $methodName = $action === 'detail' ? 'getDetail' : $action;
        if (! preg_match('/function\s+' . preg_quote($methodName, '/') . '\s*\(/', $contents)) {
            $method = $this->controllerMethodTemplate(
                $action,
                $actionClass,
                $queryClass,
                $createRequestClass,
                $searchRequestClass
            );
            $contents = preg_replace('/\n}\s*$/', "\n{$method}\n}\n", $contents, 1) ?? $contents;
        }

        $files->put($path, $contents);
        $this->line("Patched: {$path} ({$action})");
    }

    private function controllerMethodTemplate(
        string $action,
        string $actionClass,
        string $queryClass,
        string $createRequestClass,
        string $searchRequestClass
    ): string {
        return match ($action) {
            'create' => '    public function create(' . $actionClass . ' $action, ' . $createRequestClass . " \$request, string \$operation = 'create')\n"
                . "    {\n"
                . "        return \$this->traitCreate(\$action, \$request, \$operation);\n"
                . "    }\n",
            'update' => '    public function update(' . $actionClass . ' $action, ' . $createRequestClass . " \$request, \$id, string \$operation = 'update')\n"
                . "    {\n"
                . "        return \$this->traitUpdate(\$action, \$request, \$id, \$operation);\n"
                . "    }\n",
            'delete' => '    public function delete(' . $actionClass . " \$action, \$id, string \$operation = 'delete')\n"
                . "    {\n"
                . "        return \$this->traitDelete(\$action, \$id, \$operation);\n"
                . "    }\n",
            'search' => '    public function search(' . $searchRequestClass . " \$request)\n"
                . "    {\n"
                . '        $query = app()->makeWith(' . $queryClass . "::class, ['request' => \$request]);\n"
                . "        return \$this->traitSearch(\$query);\n"
                . "    }\n",
            'detail' => '    public function getDetail(' . $queryClass . " \$query, \$id)\n"
                . "    {\n"
                . "        return \$this->traitGetDetail(\$query, \$id);\n"
                . "    }\n",
        };
    }

    private function patchRoutes(
        Filesystem $files,
        string $apiRoutesPath,
        string $module,
        string $name,
        string $controllerClass,
        string $action
    ): void {
        if (! $files->exists($apiRoutesPath)) {
            return;
        }

        $contents = $files->get($apiRoutesPath);

        $controllerImport = "Modules\\{$module}\\Http\\Controllers\\{$controllerClass}";
        if (! str_contains($contents, "use {$controllerImport};")) {
            $contents = preg_replace(
                '/^<\?php\n\n/m',
                "<?php\n\nuse {$controllerImport};\n\n",
                $contents,
                1
            ) ?? $contents;
        }

        $methodName = $action === 'detail' ? 'getDetail' : $action;
        if (str_contains($contents, "[{$controllerClass}::class, '{$methodName}']")) {
            if ($contents !== $files->get($apiRoutesPath)) {
                $files->put($apiRoutesPath, $contents);
                $this->line("Patched: {$apiRoutesPath} (controller import)");
            }
            return;
        }

        $routeLine = match ($action) {
            'create' => "            Route::post('/', [{$controllerClass}::class, 'create']);\n",
            'update' => "            Route::put('{id}', [{$controllerClass}::class, 'update']);\n",
            'delete' => "            Route::delete('{id}', [{$controllerClass}::class, 'delete']);\n",
            'search' => "            Route::post('search', [{$controllerClass}::class, 'search']);\n",
            'detail' => "            Route::get('{id}', [{$controllerClass}::class, 'getDetail']);\n",
        };

        $prefix = Str::plural(Str::snake($name));
        $needle = "prefix('{$prefix}')";

        if (str_contains($contents, $needle)) {
            $contents2 = preg_replace(
                "/(prefix\('{$prefix}'\)->group\(function \(\) \{\n)/",
                "$1{$routeLine}",
                $contents,
                1
            ) ?? $contents;

            if ($contents2 !== $contents) {
                $files->put($apiRoutesPath, $contents2);
                $this->line("Patched: {$apiRoutesPath} ({$action} route)");
            } elseif ($contents !== $files->get($apiRoutesPath)) {
                $files->put($apiRoutesPath, $contents);
                $this->line("Patched: {$apiRoutesPath} (controller import)");
            }
            return;
        }

        $block = "\n        Route::middleware('auth:sanctum')->prefix('{$prefix}')->group(function () {\n{$routeLine}        });\n";
        $contents2 = preg_replace(
            "/(require\s+__DIR__\s*\.\s*['\"]\\/auth\\.php['\"];\s*\n)/",
            "$1{$block}",
            $contents,
            1
        ) ?? $contents;

        if ($contents2 !== $contents) {
            $files->put($apiRoutesPath, $contents2);
            $this->line("Patched: {$apiRoutesPath} (added {$prefix} group)");
            return;
        }

        $contents3 = preg_replace('/\n\s*\}\);\s*$/', $block . "\n});\n", $contents, 1) ?? $contents;
        if ($contents3 !== $contents) {
            $files->put($apiRoutesPath, $contents3);
            $this->line("Patched: {$apiRoutesPath} (appended {$prefix} group)");
        }
    }
}
