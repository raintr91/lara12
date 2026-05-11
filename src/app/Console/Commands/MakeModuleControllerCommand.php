<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\AsksToOverwriteExisting;
use App\Console\Commands\Concerns\GeneratesModuleFiles;
use App\Console\Commands\Concerns\GeneratesModuleTests;
use Illuminate\Support\Str;

class MakeModuleControllerCommand extends BaseCommand
{
    use GeneratesModuleFiles;
    use AsksToOverwriteExisting;
    use GeneratesModuleTests;

    private string $controllerLayer = 'feature';

    protected $signature = 'm:controller {module : Module name (StudlyCase)} {name : Controller name (StudlyCase, no Controller suffix)}
                            {--create-request= : yes|no, skip asking create request step}
                            {--search-request= : yes|no, skip asking search request step}
                            {--action-class= : yes|no, skip asking action class step}
                            {--query-class= : yes|no, skip asking query class step}
                            {--resource-class= : yes|no, skip asking resource class step}
                            {--shared-model= : yes|no, skip asking shared app model step}
                            {--overwrite-controller= : yes|no, skip asking overwrite controller step}
                            {--wire-create= : yes|no, skip asking create endpoint wiring}
                            {--wire-update= : yes|no, skip asking update endpoint wiring}
                            {--wire-delete= : yes|no, skip asking delete endpoint wiring}
                            {--wire-search= : yes|no, skip asking search endpoint wiring}
                            {--wire-detail= : yes|no, skip asking detail endpoint wiring}
                            {--select-items= : yes|no, skip asking select-items wiring}
                            {--controller-test-layer=feature : controller test layer: feature|unit}
                            {--yes : Force yes/overwrite for all prompt steps}
                            {--skip-questions : Do not ask questions for unspecified options; use defaults}';
    protected $description = 'Interactively create a controller and optionally related request, action, query, resource classes for a module.';

    public function handle(): int
    {
        $this->controllerLayer = strtolower((string) $this->option('controller-test-layer'));
        if (! in_array($this->controllerLayer, ['feature', 'unit'], true)) {
            $this->error("Invalid --controller-test-layer [{$this->controllerLayer}]. Allowed: feature, unit");
            return 1;
        }

        $module = Str::studly($this->argument('module'));
        $controllerClass = $this->studlyWithSuffix((string) $this->argument('name'), 'Controller');
        $name = substr($controllerClass, 0, max(0, strlen($controllerClass) - strlen('Controller')));
        $files = $this->laravel['files'];

        $moduleRoot = $this->moduleRoot($module);

        $createRequestClass = $this->studlyWithSuffix($name . 'Create', 'Request');
        $searchRequestClass = $this->studlyWithSuffix($name . 'Search', 'Request');
        $actionClass = $this->studlyWithSuffix($name, 'Action');
        $queryClass = $this->studlyWithSuffix($name, 'Query');
        $resourceClass = $this->studlyWithSuffix($name, 'Resource');

        $createRequestPath = $moduleRoot . "/Http/Requests/{$createRequestClass}.php";
        $searchRequestPath = $moduleRoot . "/Http/Requests/{$searchRequestClass}.php";
        $actionPath = $moduleRoot . "/Http/Actions/{$actionClass}.php";
        $queryPath = $moduleRoot . "/Http/Queries/{$queryClass}.php";
        $resourcePath = $moduleRoot . "/Http/Resources/{$resourceClass}.php";

        // Step 1: CreateRequest
        $createRequestChoice = $this->askCreateOrOverwrite($files, 'create-request', "Create {$createRequestClass} for module {$module}?", $createRequestPath, true);
        if ($createRequestChoice === 'create' || $createRequestChoice === 'overwrite') {
            $args = [
                'module' => $module,
                'name' => $createRequestClass,
            ];
            if ($createRequestChoice === 'overwrite') {
                $args['--force'] = true;
            }
            $this->call('m:request', $args);
        }

        // Step 2: SearchRequest
        $searchRequestChoice = $this->askCreateOrOverwrite($files, 'search-request', "Create {$searchRequestClass} for module {$module}?", $searchRequestPath, true);
        if ($searchRequestChoice === 'create' || $searchRequestChoice === 'overwrite') {
            $args = [
                'module' => $module,
                'name' => $searchRequestClass,
            ];
            if ($searchRequestChoice === 'overwrite') {
                $args['--force'] = true;
            }
            $this->call('m:request', $args);
        }

        // Step 3: Action
        $actionChoice = $this->askCreateOrOverwrite($files, 'action-class', "Create {$actionClass} for module {$module}?", $actionPath, true);
        if ($actionChoice === 'create' || $actionChoice === 'overwrite') {
            $args = [
                'module' => $module,
                'name' => $actionClass,
            ];
            if ($actionChoice === 'overwrite') {
                $args['--force'] = true;
            }
            $this->call('m:action', $args);
        }

        // Step 4: Query
        $queryChoice = $this->askCreateOrOverwrite($files, 'query-class', "Create {$queryClass} for module {$module}?", $queryPath, true);
        if ($queryChoice === 'create' || $queryChoice === 'overwrite') {
            $args = [
                'module' => $module,
                'name' => $queryClass,
                '--yes' => $this->shouldForceYes(),
            ];
            if ($queryChoice === 'overwrite') {
                $args['--force'] = true;
            }
            $this->call('m:query', $args);
        }

        // Step 5: Resource
        $resourceChoice = $this->askCreateOrOverwrite($files, 'resource-class', "Create {$resourceClass} for module {$module}?", $resourcePath, true);
        if ($resourceChoice === 'create' || $resourceChoice === 'overwrite') {
            $args = [
                'module' => $module,
                'name' => $resourceClass,
            ];
            if ($resourceChoice === 'overwrite') {
                $args['--force'] = true;
            }
            $this->call('m:resource', $args);
        }

        // Step 5.5: Optional shared app model
        if ($this->askYesNo('shared-model', "Create shared app model {$name}?", false)) {
            $this->call('m:model', ['name' => $name]);
        }

        // Step 6: Always create controller skeleton
        $controllerName = $controllerClass;
        $controllerPath = base_path("Modules/{$module}/Http/Controllers/{$controllerName}.php");
        $baseController = $module . 'Controller';

        $forceControllerTest = false;

        if ($files->exists($controllerPath)) {
            $overwriteController = $this->askYesNo('overwrite-controller', "File exists: {$controllerPath}. Overwrite?", false);
            if (! $overwriteController) {
                $this->info("Keeping existing {$controllerName}.");
            } else {
                $this->writeControllerSkeleton($files, $module, $baseController, $controllerName, $controllerPath);
                $this->info("Created {$controllerName} for module {$module}.");
                $forceControllerTest = true;
            }
        } else {
            $this->writeControllerSkeleton($files, $module, $baseController, $controllerName, $controllerPath);
            $this->info("Created {$controllerName} for module {$module}.");
            $forceControllerTest = false;
        }

        $selectedActions = $this->askActionsToWire($files, $actionPath, $queryPath);
        foreach ($selectedActions as $action) {
            $this->call('add:action', [
                'module' => $module,
                'controller' => $name,
                'action' => $action,
                '--yes' => $this->shouldForceYes(),
                '--skip-questions' => $this->shouldSkipQuestions(),
            ]);
        }

        if ($this->askYesNo('select-items', 'Enable select-items API for this controller?', false)) {
            $this->newLine();
            $this->info('Adding select-items API...');
            $this->call('add:select-item', [
                'module' => $module,
                'controller' => $name,
                '--yes' => $this->shouldForceYes(),
                '--skip-questions' => $this->shouldSkipQuestions(),
            ]);
        }

        $this->ensureGeneratedClassTest($files, $module, 'controller', $controllerName, $forceControllerTest);

        return 0;
    }

    protected function controllerTestLayer(): string
    {
        return $this->controllerLayer;
    }

    private function askActionsToWire($files, string $actionPath, string $queryPath): array
    {
        $hasAction = $files->exists($actionPath);
        $hasQuery = $files->exists($queryPath);

        $actions = [];

        if ($this->askYesNo('wire-create', 'Add create endpoint trait/wrapper?', $hasAction)) {
            $actions[] = 'create';
        }
        if ($this->askYesNo('wire-update', 'Add update endpoint trait/wrapper?', $hasAction)) {
            $actions[] = 'update';
        }
        if ($this->askYesNo('wire-delete', 'Add delete endpoint trait/wrapper?', $hasAction)) {
            $actions[] = 'delete';
        }
        if ($this->askYesNo('wire-search', 'Add search endpoint trait/wrapper?', $hasQuery)) {
            $actions[] = 'search';
        }
        if ($this->askYesNo('wire-detail', 'Add detail endpoint trait/wrapper?', $hasQuery)) {
            $actions[] = 'detail';
        }

        return $actions;
    }

    private function writeControllerSkeleton($files, string $module, string $baseController, string $controllerName, string $controllerPath): void
    {
        $stubPath = base_path('stubs/modules/scaffold/module-controller.stub');
        if ($files->exists($stubPath)) {
            $stub = $files->get($stubPath);
            $stub = str_replace([
                '$MODULE$',
                '$BASE_CONTROLLER$',
                '$CONTROLLER$',
                '$EXTRA_IMPORTS$',
                '$CLASS_TRAITS$',
            ], [
                $module,
                $baseController,
                $controllerName,
                '',
                '',
            ], $stub);
        } else {
            $imports = "use Modules\\{$module}\\Http\\Controllers\\{$baseController};\n";
            $stub = "<?php\n\nnamespace Modules\\{$module}\\Http\\Controllers;\n\n{$imports}\nclass {$controllerName} extends {$baseController}\n{\n    // ...\n}\n";
        }

        if (! $files->exists(dirname($controllerPath))) {
            $files->makeDirectory(dirname($controllerPath), 0775, true);
        }

        $files->put($controllerPath, $stub);
    }
}
