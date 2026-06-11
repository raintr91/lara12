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

    protected $aliases = ['m:add-action'];

    protected $signature = 'add:action {module : Module name (StudlyCase)} {controller : Controller name (StudlyCase, no Controller suffix)} {action : create|update|delete|bulk-delete|search|detail}
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
            $this->error('Invalid action. Supported: create, update, delete, bulk-delete, search, detail');
            return 1;
        }

        $actionClass = $this->studlyWithSuffix($name, 'Action');
        $queryClass = $this->studlyWithSuffix($name, 'Query');
        $createRequestClass = $this->studlyWithSuffix($name . 'Create', 'Request');
        $searchRequestClass = $this->studlyWithSuffix($name . 'Search', 'Request');
        $bulkDeleteRequestClass = $this->studlyWithSuffix($name . 'BulkDelete', 'Request');

        $actionPath = $moduleRoot . "/Http/Actions/{$actionClass}.php";
        $queryPath = $moduleRoot . "/Http/Queries/{$queryClass}.php";
        $createRequestPath = $moduleRoot . "/Http/Requests/{$createRequestClass}.php";
        $searchRequestPath = $moduleRoot . "/Http/Requests/{$searchRequestClass}.php";
        $bulkDeleteRequestPath = $moduleRoot . "/Http/Requests/{$bulkDeleteRequestClass}.php";

        if (in_array($action, ['create', 'update', 'delete', 'bulk-delete'], true)) {
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

        if ($action === 'bulk-delete') {
            if (! $this->ensureBulkDeleteRequestExists($files, $module, $bulkDeleteRequestClass, $bulkDeleteRequestPath)) {
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
            $searchRequestClass,
            $bulkDeleteRequestClass
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
            'bulk-delete', 'bulkdelete', 'multiple-delete', 'multipledelete' => 'bulk-delete',
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

    private function ensureBulkDeleteRequestExists(Filesystem $files, string $module, string $class, string $path): bool
    {
        if ($files->exists($path)) {
            return true;
        }

        $shouldCreate = $this->askYesNo('yes', "Bulk delete request not found. Create {$class} for module {$module}?", true);

        if (! $shouldCreate) {
            $this->warn('This endpoint requires a BulkDelete request class. Aborting.');
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
        string $searchRequestClass,
        string $bulkDeleteRequestClass = ''
    ): void {
        $contents = $files->get($path);

        $extraImports = [];
        $traitImportMap = [
            'create' => 'App\\Http\\Controllers\\Traits\\EntryCreateTrait',
            'update' => 'App\\Http\\Controllers\\Traits\\EntryUpdateTrait',
            'delete' => 'App\\Http\\Controllers\\Traits\\EntryDeleteTrait',
            'bulk-delete' => 'App\\Http\\Controllers\\Traits\\EntryBulkDeleteTrait',
            'search' => 'App\\Http\\Controllers\\Traits\\EntrySearchTrait',
            'detail' => 'App\\Http\\Controllers\\Traits\\EntryDetailTrait',
        ];
        if (in_array($action, ['create', 'update', 'delete', 'bulk-delete'], true)) {
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
        if ($action === 'bulk-delete' && $bulkDeleteRequestClass !== '') {
            $extraImports[] = "Modules\\{$module}\\Http\\Requests\\{$bulkDeleteRequestClass}";
        }
        if (isset($traitImportMap[$action])) {
            $extraImports[] = $traitImportMap[$action];
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

        $needsActionDependency = in_array($action, ['create', 'update', 'delete', 'bulk-delete'], true);
        $needsQueryDependency = in_array($action, ['search', 'detail'], true);

        if (($needsActionDependency || $needsQueryDependency) && ! preg_match('/function\s+__construct\s*\(/', $contents)) {
            $dependencyBlock = $this->buildControllerDependencyBlock($actionClass, $queryClass, $needsActionDependency, $needsQueryDependency);
            $contents = preg_replace(
                '/class\s+\w+\s+extends\s+\w+\s*\{\n/m',
                "$0{$dependencyBlock}",
                $contents,
                1
            ) ?? $contents;
        }

        // Always add the corresponding trait for the action, never generate the CRUD method
        $traitMap = [
            'create' => 'EntryCreateTrait',
            'update' => 'EntryUpdateTrait',
            'delete' => 'EntryDeleteTrait',
            'bulk-delete' => 'EntryBulkDeleteTrait',
            'search' => 'EntrySearchTrait',
            'detail' => 'EntryDetailTrait',
        ];
        $trait = $traitMap[$action] ?? null;
        if ($trait && !preg_match('/use\\s+' . preg_quote($trait, '/') . '\s*;/', $contents)) {
            // Insert use trait after class opening or after constructor
            if (preg_match('/(class\s+\w+\s+extends\s+\w+\s*\{\n)/', $contents, $m, PREG_OFFSET_CAPTURE)) {
                $pos = $m[1][1] + strlen($m[1][0]);
                $contents = substr($contents, 0, $pos)
                    . "    use {$trait};\n"
                    . substr($contents, $pos);
            }
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
            'create' => "    public function create({$createRequestClass} \$request)\n"
                . "    {\n"
                . "        return \$this->success(\$this->action->create(\$request->validated()), 'Created successfully', 201);\n"
                . "    }\n",
            'update' => "    public function update({$createRequestClass} \$request, \$id)\n"
                . "    {\n"
                . "        return \$this->success(\$this->action->update((int) \$id, \$request->validated()), 'Updated successfully');\n"
                . "    }\n",
            'delete' => "    public function delete(\$id)\n"
                . "    {\n"
                . "        return \$this->success(\$this->action->delete((int) \$id), 'Deleted successfully');\n"
                . "    }\n",
            'search' => "    public function search({$searchRequestClass} \$request)\n"
                . "    {\n"
                . "        return \$this->success(\$this->query->paginate(), 'Retrieved successfully');\n"
                . "    }\n",
            'detail' => "    public function getDetail(\$id)\n"
                . "    {\n"
                . "        return \$this->success(\$this->query->findById(\$id), 'Retrieved successfully');\n"
                . "    }\n",
        };
    }

    private function buildControllerDependencyBlock(string $actionClass, string $queryClass, bool $needsActionDependency, bool $needsQueryDependency): string
    {
        $constructorParams = [];

        if ($needsActionDependency) {
            $constructorParams[] = 'private readonly ' . $actionClass . ' $action';
        }

        if ($needsQueryDependency) {
            $constructorParams[] = 'private readonly ' . $queryClass . ' $query';
        }

        $block = '';
        $block .= '    public function __construct('.implode(', ', $constructorParams).")\n";
        $block .= "    {\n";
        $block .= "    }\n\n";

        return $block;
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

        $methodName = match ($action) {
            'detail' => 'getDetail',
            'bulk-delete' => 'bulkDelete',
            default => $action,
        };

        if (str_contains($contents, "[{$controllerClass}::class, '{$methodName}']")) {
            if ($contents !== $files->get($apiRoutesPath)) {
                $files->put($apiRoutesPath, $contents);
                $this->line("Patched: {$apiRoutesPath} (controller import)");
            }
            return;
        }

        $routeLine = match ($action) {
            'create' => "            Route::post('/', [{$controllerClass}::class, 'create']);\n",
            'update' => "            Route::put('edit/{id}', [{$controllerClass}::class, 'update']);\n",
            'delete' => "            Route::delete('delete/{id}', [{$controllerClass}::class, 'delete']);\n",
            'bulk-delete' => "       Route::post('bulk-delete', [{$controllerClass}::class, 'bulkDelete']);\n",
            'search' => "            Route::get('search', [{$controllerClass}::class, 'search']);\n",
            'detail' => "            Route::get('detail/{id}', [{$controllerClass}::class, 'getDetail']);\n",
        };

        $prefix = Str::kebab($name);
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

        $block = "\n        Route::prefix('{$prefix}')->group(function () {\n{$routeLine}        });\n";
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
