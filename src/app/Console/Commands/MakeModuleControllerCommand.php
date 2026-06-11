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
                            {--force : Overwrite controller file if exists}
                            {--create-request= : yes|no, skip asking create request step}
                            {--search-request= : yes|no, skip asking search request step}
                            {--action-class= : yes|no, skip asking action class step}
                            {--query-class= : yes|no, skip asking query class step}
                            {--resource-class= : yes|no, skip asking resource class step}
                            {--shared-model= : yes|no, skip asking shared app model step}
                            {--path-model= : App\\Models subpath when creating shared model (e.g. Platform, Tenant). Omit or empty = root}
                            {--overwrite-controller= : yes|no, skip asking overwrite controller step}
                            {--wire-create= : yes|no, skip asking create endpoint wiring}
                            {--wire-update= : yes|no, skip asking update endpoint wiring}
                            {--wire-delete= : yes|no, skip asking delete endpoint wiring}
                            {--wire-search= : yes|no, skip asking search endpoint wiring}
                            {--wire-detail= : yes|no, skip asking detail endpoint wiring}
                            {--wire-multiple-delete= : yes|no, skip asking bulk/multiple delete endpoint wiring}
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
        $createRequestChoice = $this->askCreateOrOverwriteForController($files, 'create-request', "Create {$createRequestClass} for module {$module}?", $createRequestPath, true);
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
        $searchRequestChoice = $this->askCreateOrOverwriteForController($files, 'search-request', "Create {$searchRequestClass} for module {$module}?", $searchRequestPath, true);
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

        $pathModel = $this->resolveSharedAppModelPath();
        if ($pathModel !== '') {
            $modelFqn = 'App\\Models\\' . str_replace('/', '\\', trim($pathModel, '/')) . '\\' . $name;
        } else {
            $modelFqn = 'App\\Models\\' . $name;
        }
        // Step 3: Action
        $actionChoice = $this->askCreateOrOverwriteForController($files, 'action-class', "Create {$actionClass} for module {$module}?", $actionPath, true);
        if ($actionChoice === 'create' || $actionChoice === 'overwrite') {
            $args = [
                'module' => $module,
                'name' => $actionClass,
            ];
            // Nếu có path-model thì truyền model-fqn
            if ($pathModel !== '') {
                $args['--model-fqn'] = $modelFqn;
            }
            if ($actionChoice === 'overwrite') {
                $args['--force'] = true;
            }
            $this->call('m:action', $args);
        }

        // Step 4: Query
        $queryChoice = $this->askCreateOrOverwriteForController($files, 'query-class', "Create {$queryClass} for module {$module}?", $queryPath, true);
        if ($queryChoice === 'create' || $queryChoice === 'overwrite') {
            $args = [
                'module' => $module,
                'name' => $queryClass,
                '--yes' => $this->shouldForceYes(),
            ];
            if ($pathModel !== '') {
                $args['--model-fqn'] = $modelFqn;
            }
            if ($queryChoice === 'overwrite') {
                $args['--force'] = true;
            }
            $this->call('m:query', $args);
        }

        // Step 5: Resource
        $resourceChoice = $this->askCreateOrOverwriteForController($files, 'resource-class', "Create {$resourceClass} for module {$module}?", $resourcePath, true);
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

        // Step 5.5: Optional shared app model (m:model {name} {path?})
        if ($this->askYesNoForController('shared-model', "Create shared app model {$name}?", false)) {
            $this->createSharedAppModel($name);
        }

        // Step 6: Always create controller skeleton
        $controllerName = $controllerClass;
        $controllerPath = base_path("Modules/{$module}/Http/Controllers/{$controllerName}.php");
        $baseController = $module . 'Controller';
        $hasActionDependency = $files->exists($actionPath);
        $hasQueryDependency = $files->exists($queryPath);

        $forceControllerTest = false;

        if ($files->exists($controllerPath)) {
            $forceOption = (bool) $this->option('force');
            if ($forceOption) {
                $overwriteController = true;
            } else {
                $overwriteController = $this->askYesNoForController('overwrite-controller', "File exists: {$controllerPath}. Overwrite?", false);
            }
            if (! $overwriteController) {
                $this->info("Keeping existing {$controllerName}.");
            } else {
                $this->writeControllerSkeleton($files, $module, $baseController, $controllerName, $controllerPath, $hasActionDependency, $hasQueryDependency);
                $this->info("Created {$controllerName} for module {$module}.");
                $forceControllerTest = true;
            }
        } else {
            $this->writeControllerSkeleton($files, $module, $baseController, $controllerName, $controllerPath, $hasActionDependency, $hasQueryDependency);
            $this->info("Created {$controllerName} for module {$module}.");
            $forceControllerTest = false;
        }

        $selectedActions = $this->askActionsToWire($files, $actionPath, $queryPath);
        foreach ($selectedActions as $action) {
            $args = [
                'module' => $module,
                'controller' => $name,
                'action' => $action,
                '--skip-questions' => $this->shouldSkipQuestions(),
            ];
            if ($this->shouldForceYes()) {
                $args['--skip-questions'] = true;
            }
            $this->call('add:action', $args);
        }

        if ($this->askYesNoForController('select-items', 'Enable select-items API for this controller?', false)) {
            $this->newLine();
            $this->info('Adding select-items API...');
            $addSelectItemArgs = [
                'module' => $module,
                'controller' => $name,
                '--skip-questions' => $this->shouldSkipQuestions(),
            ];
            if ($this->shouldForceYes()) {
                $addSelectItemArgs['--skip-questions'] = true;
            }
            // Nếu có path-model thì truyền model-fqn
            $pathModel = $this->resolveSharedAppModelPath();
            if ($pathModel !== '') {
                $modelFqn = 'App\\Models\\' . str_replace('/', '\\', trim($pathModel, '/')) . '\\' . $name;
                $addSelectItemArgs['--model-fqn'] = $modelFqn;
            } else {
                $modelFqn = 'App\\Models\\' . $name;
            }
            $this->call('add:select-item', $addSelectItemArgs);
        }

        $this->ensureGeneratedClassTest($files, $module, 'controller', $controllerName, $forceControllerTest);

        return 0;
    }


    private function askActionsToWire($files, string $actionPath, string $queryPath): array
    {
        $hasAction = $files->exists($actionPath);
        $hasQuery = $files->exists($queryPath);
        $hasExplicitWireOptions = $this->hasExplicitWireOptions();

        $actions = [];

        if ($this->askYesNoForController('wire-create', 'Add create endpoint trait/wrapper?', $hasExplicitWireOptions ? false : $hasAction)) {
            $actions[] = 'create';
        }
        if ($this->askYesNoForController('wire-update', 'Add update endpoint trait/wrapper?', $hasExplicitWireOptions ? false : $hasAction)) {
            $actions[] = 'update';
        }
        if ($this->askYesNoForController('wire-delete', 'Add delete endpoint trait/wrapper?', $hasExplicitWireOptions ? false : $hasAction)) {
            $actions[] = 'delete';
        }
        if ($this->askYesNoForController('wire-search', 'Add search endpoint trait/wrapper?', $hasExplicitWireOptions ? false : $hasQuery)) {
            $actions[] = 'search';
        }
        if ($this->askYesNoForController('wire-detail', 'Add detail endpoint trait/wrapper?', $hasExplicitWireOptions ? false : $hasQuery)) {
            $actions[] = 'detail';
        }
        if ($this->askYesNoForController('wire-multiple-delete', 'Add bulk/multiple delete endpoint trait?', false)) {
            $actions[] = 'bulk-delete';
        }

        return $actions;
    }

    private function hasExplicitWireOptions(): bool
    {
        foreach (['wire-create', 'wire-update', 'wire-delete', 'wire-search', 'wire-detail', 'wire-multiple-delete'] as $option) {
            if ($this->resolveYesNoOption($option) !== null) {
                return true;
            }
        }

        return false;
    }

    private function askYesNoForController(string $optionName, string $question, bool $default): bool
    {
        $resolved = $this->resolveYesNoOption($optionName);
        if ($resolved !== null) {
            return $resolved;
        }

        if ($this->shouldForceYes() || $this->shouldSkipQuestions() || ! $this->input->isInteractive()) {
            return $default;
        }

        return $this->confirm($question, $default);
    }

    private function askCreateOrOverwriteForController(
        $files,
        string $optionName,
        string $question,
        string $targetPath,
        bool $defaultYes = true
    ): string {
        $resolved = $this->resolveYesNoOption($optionName);
        if ($resolved === false) {
            return 'no';
        }

        if ($resolved === true) {
            return $files->exists($targetPath) ? 'overwrite' : 'create';
        }

        if ($this->shouldForceYes() || $this->shouldSkipQuestions() || ! $this->input->isInteractive()) {
            if (! $defaultYes) {
                return 'no';
            }

            return $files->exists($targetPath) ? 'keep' : 'create';
        }

        return $this->askCreateOrOverwrite($files, $optionName, $question, $targetPath, $defaultYes);
    }

    private function writeControllerSkeleton($files, string $module, string $baseController, string $controllerName, string $controllerPath, bool $hasActionDependency = false, bool $hasQueryDependency = false): void
    {
        $stubPath = base_path('stubs/modules/scaffold/module-controller.stub');
        $actionClass = Str::beforeLast($controllerName, 'Controller').'Action';
        $queryClass = Str::beforeLast($controllerName, 'Controller').'Query';

        $constructorParams = [];
        $imports = [];
        $traits = [];

        // Entry trait mapping
        $entryTraitMap = [
            'create' => 'EntryCreateTrait',
            'update' => 'EntryUpdateTrait',
            'delete' => 'EntryDeleteTrait',
            'search' => 'EntrySearchTrait',
            'detail' => 'EntryDetailTrait',
            'select' => 'EntrySelectTrait',
        ];

        if ($hasActionDependency) {
            $imports[] = "Modules\\{$module}\\Http\\Actions\\{$actionClass}";
            $constructorParams[] = 'private readonly ' . $actionClass . ' $action';
        }

        if ($hasQueryDependency) {
            $imports[] = "Modules\\{$module}\\Http\\Queries\\{$queryClass}";
            $constructorParams[] = 'private readonly ' . $queryClass . ' $query';
        }

        // Add use traits block
        $traitsBlock = $traits ? "    use " . implode(', ', $traits) . ";\n" : '';

        if ($files->exists($stubPath)) {
            $stub = $files->get($stubPath);
            $stub = str_replace([
                '$MODULE$',
                '$BASE_CONTROLLER$',
                '$CONTROLLER$',
                '$EXTRA_IMPORTS$',
                '$PROPERTIES$',
                '$CONSTRUCTOR_SIGNATURE$',
                '$CONSTRUCTOR_BODY$',
                '$TRAITS$',
            ], [
                $module,
                $baseController,
                $controllerName,
                implode("\n", array_map(fn (string $import) => "use {$import};", $imports)),
                '',
                implode(', ', $constructorParams),
                '',
                $traitsBlock,
            ], $stub);
        } else {
            $importLines = array_merge([
                "use Modules\\{$module}\\Http\\Controllers\\{$baseController};",
            ], array_map(fn (string $import) => "use {$import};", $imports));

            $constructorBlock = '    public function __construct('.implode(', ', $constructorParams).")\n    {\n";
            $constructorBlock .= "    }\n\n";
            $traitsBlock = $traits ? "    use " . implode(', ', $traits) . ";\n" : '';

            $stub = "<?php\n\nnamespace Modules\\{$module}\\Http\\Controllers;\n\n".implode("\n", $importLines)."\n\nclass {$controllerName} extends {$baseController}\n{\n{$constructorBlock}{$traitsBlock}}\n";
        }

        if (! $files->exists(dirname($controllerPath))) {
            $files->makeDirectory(dirname($controllerPath), 0775, true);
        }

        $files->put($controllerPath, $stub);

    }

    private function createSharedAppModel(string $name): void
    {
        $path = $this->resolveSharedAppModelPath();

        $args = [
            'name' => $name,
            '--skip-questions' => $this->shouldSkipQuestions(),
        ];

        if ($path !== '') {
            $args['path'] = $path;
        }

        if ($this->shouldForceYes()) {
            $args['--yes'] = true;
        }

        $this->call('m:model', $args);

        if ($path !== '') {
            $this->line("App model target: <info>App\\Models\\{$path}\\{$name}</info>");
        } else {
            $this->line("App model target: <info>App\\Models\\{$name}</info>");
        }
    }

    /**
     * Path under App/Models (e.g. Platform). Empty string = root App\Models.
     */
    private function resolveSharedAppModelPath(): string
    {
        if ($this->hasOption('path-model')) {
            $fromOption = $this->option('path-model');
            if ($fromOption !== null) {
                return trim(str_replace('\\', '/', (string) $fromOption));
            }
        }

        if ($this->shouldSkipQuestions() || ! $this->input->isInteractive()) {
            return '';
        }

        return trim(str_replace('\\', '/', (string) $this->ask(
            'App model path under App/Models? (Enter = none, e.g. Platform)',
            ''
        )));
    }
}
