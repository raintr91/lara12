<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\AddActionCommand;
use Illuminate\Filesystem\Filesystem;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class AddActionCommandTest extends TestCase
{
    private array $tmpModules = [];

    protected function tearDown(): void
    {
        $files = new Filesystem();
        foreach ($this->tmpModules as $module) {
            $files->deleteDirectory(base_path("Modules/{$module}"));
        }

        parent::tearDown();
    }

    public function test_normalize_action_aliases(): void
    {
        $command = new AddActionCommand();

        $normalize = new ReflectionMethod(AddActionCommand::class, 'normalizeAction');
        $normalize->setAccessible(true);

        $this->assertSame('search', $normalize->invoke($command, 'list'));
        $this->assertSame('detail', $normalize->invoke($command, 'get-detail'));
        $this->assertSame('bulk-delete', $normalize->invoke($command, 'multiple-delete'));
        $this->assertSame('create', $normalize->invoke($command, 'create'));
        $this->assertNull($normalize->invoke($command, 'invalid'));
    }

    public function test_controller_method_template_contains_expected_methods(): void
    {
        $command = new AddActionCommand();

        $template = new ReflectionMethod(AddActionCommand::class, 'controllerMethodTemplate');
        $template->setAccessible(true);

        $create = $template->invoke($command, 'create', 'UserAction', 'UserQuery', 'UserCreateRequest', 'UserSearchRequest');
        $search = $template->invoke($command, 'search', 'UserAction', 'UserQuery', 'UserCreateRequest', 'UserSearchRequest');
        $detail = $template->invoke($command, 'detail', 'UserAction', 'UserQuery', 'UserCreateRequest', 'UserSearchRequest');

        $this->assertStringContainsString('function create(', $create);
        $this->assertStringContainsString('$this->action->create', $create);

        $this->assertStringContainsString('function search(', $search);
        $this->assertStringContainsString('$this->query->paginate', $search);

        $this->assertStringContainsString('function getDetail(', $detail);
        $this->assertStringContainsString('findById', $detail);
    }

    public function test_handle_returns_error_for_invalid_action(): void
    {
        $module = $this->makeModuleWithController('TmpAddInvalid' . uniqid());

        $this->artisan('add:action', [
            'module' => $module,
            'controller' => 'Sample',
            'action' => 'invalid',
            '--yes' => true,
            '--skip-questions' => true,
        ])->assertExitCode(1);
    }

    public function test_handle_patches_controller_and_routes_for_search_action(): void
    {
        $module = $this->makeModuleWithController('TmpAddSearch' . uniqid());
        $files = new Filesystem();

        $files->put(base_path("Modules/{$module}/Http/Queries/SampleQuery.php"), "<?php\nnamespace Modules\\{$module}\\Http\\Queries;\nclass SampleQuery {}\n");
        $files->put(base_path("Modules/{$module}/Http/Requests/SampleSearchRequest.php"), "<?php\nnamespace Modules\\{$module}\\Http\\Requests;\nclass SampleSearchRequest {}\n");

        $this->artisan('add:action', [
            'module' => $module,
            'controller' => 'Sample',
            'action' => 'search',
            '--yes' => true,
            '--skip-questions' => true,
        ])->assertExitCode(0);

        $controller = $files->get(base_path("Modules/{$module}/Http/Controllers/SampleController.php"));
        $routes = $files->get(base_path("Modules/{$module}/Routes/api.php"));

        $this->assertStringContainsString('EntrySearchTrait', $controller);
        $this->assertStringContainsString("Route::post('search'", $routes);
    }

    public function test_patch_routes_handles_existing_action_and_missing_file(): void
    {
        $command = new AddActionCommand();
        $this->attachOutput($command);
        $files = new Filesystem();
        $method = new ReflectionMethod(AddActionCommand::class, 'patchRoutes');
        $method->setAccessible(true);

        $missing = storage_path('framework/cache/missing-routes-' . uniqid() . '.php');
        $method->invoke($command, $files, $missing, 'Hook', 'Sample', 'SampleController', 'create');
        $this->assertFalse($files->exists($missing));

        $routesPath = storage_path('framework/cache/routes-existing-' . uniqid() . '.php');
        $files->put($routesPath, "<?php\n\nuse Modules\\Hook\\Http\\Controllers\\SampleController;\n\nRoute::middleware('auth:sanctum')->prefix('samples')->group(function () {\n            Route::post('/', [SampleController::class, 'create']);\n        });\n");

        $method->invoke($command, $files, $routesPath, 'Hook', 'Sample', 'SampleController', 'create');
        $routes = $files->get($routesPath);
        $this->assertSame(1, substr_count($routes, "[SampleController::class, 'create']"));

        $files->delete($routesPath);
    }

    public function test_private_ensure_methods_cover_create_and_abort_paths(): void
    {
        $files = new Filesystem();
        $module = 'TmpEnsure' . uniqid();
        $path = storage_path('framework/cache/missing-ensure-' . uniqid() . '.php');

        $command = new class extends AddActionCommand {
            public bool $answer = true;
            public array $called = [];

            protected function shouldForceYes(): bool
            {
                return false;
            }

            protected function askYesNo(string $optionName, string $question, bool $default): bool
            {
                return $this->answer;
            }

            public function call($command, array $arguments = []): int
            {
                $this->called[] = [$command, $arguments];
                return 0;
            }
        };
        $this->attachOutput($command);

        $ensureAction = new ReflectionMethod(AddActionCommand::class, 'ensureActionExists');
        $ensureAction->setAccessible(true);
        $ensureQuery = new ReflectionMethod(AddActionCommand::class, 'ensureQueryExists');
        $ensureQuery->setAccessible(true);
        $ensureRequest = new ReflectionMethod(AddActionCommand::class, 'ensureRequestExists');
        $ensureRequest->setAccessible(true);

        $command->answer = false;
        $this->assertFalse($ensureAction->invoke($command, $files, $module, 'DemoAction', $path));
        $this->assertFalse($ensureQuery->invoke($command, $files, $module, 'DemoQuery', $path));
        $this->assertFalse($ensureRequest->invoke($command, $files, $module, 'DemoRequest', $path));

        $command->answer = true;
        $this->assertTrue($ensureAction->invoke($command, $files, $module, 'DemoAction', $path));
        $this->assertTrue($ensureQuery->invoke($command, $files, $module, 'DemoQuery', $path));
        $this->assertTrue($ensureRequest->invoke($command, $files, $module, 'DemoRequest', $path));

        $this->assertSame('m:action', $command->called[0][0]);
        $this->assertSame('m:query', $command->called[1][0]);
        $this->assertSame('m:request', $command->called[2][0]);
    }

    public function test_patch_controller_covers_all_action_templates(): void
    {
        $command = new AddActionCommand();
        $this->attachOutput($command);
        $files = new Filesystem();
        $method = new ReflectionMethod(AddActionCommand::class, 'patchController');
        $method->setAccessible(true);

        $traitByAction = [
            'create' => 'EntryCreateTrait',
            'update' => 'EntryUpdateTrait',
            'delete' => 'EntryDeleteTrait',
            'bulk-delete' => 'EntryBulkDeleteTrait',
            'search' => 'EntrySearchTrait',
            'detail' => 'EntryDetailTrait',
        ];

        foreach ($traitByAction as $action => $trait) {
            $path = storage_path('framework/cache/controller-' . $action . '-' . uniqid() . '.php');
            $files->put($path, "<?php\n\nnamespace Modules\\Hook\\Http\\Controllers;\n\nclass SampleController extends HookController\n{\n}\n");

            $method->invoke($command, $files, $path, 'Hook', $action, 'SampleAction', 'SampleQuery', 'SampleCreateRequest', 'SampleSearchRequest');
            $contents = $files->get($path);

            $this->assertStringContainsString("use {$trait};", $contents);

            $files->delete($path);
        }
    }

    public function test_handle_patches_controller_and_routes_for_bulk_delete_action(): void
    {
        $module = $this->makeModuleWithController('TmpAddBulkDelete' . uniqid());
        $files = new Filesystem();

        $files->put(base_path("Modules/{$module}/Http/Actions/SampleAction.php"), "<?php\nnamespace Modules\\{$module}\\Http\\Actions;\nclass SampleAction {}\n");
        $files->put(base_path("Modules/{$module}/Http/Requests/SampleBulkDeleteRequest.php"), "<?php\n\nnamespace Modules\\{$module}\\Http\\Requests;\n\nuse App\\Http\\Requests\\BulkDeleteRequest;\n\nclass SampleBulkDeleteRequest extends BulkDeleteRequest\n{\n}\n");

        $this->artisan('add:action', [
            'module' => $module,
            'controller' => 'Sample',
            'action' => 'bulk-delete',
            '--yes' => true,
            '--skip-questions' => true,
        ])->assertExitCode(0);

        $controller = $files->get(base_path("Modules/{$module}/Http/Controllers/SampleController.php"));
        $routes = $files->get(base_path("Modules/{$module}/Routes/api.php"));

        $this->assertStringContainsString('EntryBulkDeleteTrait', $controller);
        $this->assertStringContainsString('SampleBulkDeleteRequest', $controller);
        $this->assertStringContainsString("Route::post('bulk-delete'", $routes);
        $this->assertStringContainsString("'bulkDelete'", $routes);
    }

    public function test_patch_routes_adds_group_when_prefix_does_not_exist(): void
    {
        $command = new AddActionCommand();
        $this->attachOutput($command);
        $files = new Filesystem();
        $method = new ReflectionMethod(AddActionCommand::class, 'patchRoutes');
        $method->setAccessible(true);

        $routesPath = storage_path('framework/cache/routes-block-' . uniqid() . '.php');
        $files->put($routesPath, "<?php\n\nrequire __DIR__.'/auth.php';\n");

        $method->invoke($command, $files, $routesPath, 'Hook', 'Sample', 'SampleController', 'delete');
        $routes = $files->get($routesPath);

        $this->assertStringContainsString("prefix('samples')", $routes);
        $this->assertStringContainsString("Route::delete('{id}'", $routes);

        $files->delete($routesPath);
    }

    private function makeModuleWithController(string $module): string
    {
        $this->tmpModules[] = $module;
        $files = new Filesystem();

        $moduleRoot = base_path("Modules/{$module}");
        $files->makeDirectory("{$moduleRoot}/Http/Controllers", 0775, true);
        $files->makeDirectory("{$moduleRoot}/Http/Actions", 0775, true);
        $files->makeDirectory("{$moduleRoot}/Http/Queries", 0775, true);
        $files->makeDirectory("{$moduleRoot}/Http/Requests", 0775, true);
        $files->makeDirectory("{$moduleRoot}/Routes", 0775, true);

        $files->put("{$moduleRoot}/Http/Controllers/{$module}Controller.php", "<?php\nnamespace Modules\\{$module}\\Http\\Controllers;\nclass {$module}Controller {}\n");
        $files->put("{$moduleRoot}/Http/Controllers/SampleController.php", "<?php\n\nnamespace Modules\\{$module}\\Http\\Controllers;\n\nuse Modules\\{$module}\\Http\\Controllers\\{$module}Controller;\n\nclass SampleController extends {$module}Controller\n{\n}\n");
        $files->put("{$moduleRoot}/Routes/api.php", "<?php\n\nrequire __DIR__.'/auth.php';\n");

        return $module;
    }

    private function attachOutput(AddActionCommand $command): void
    {
        $prop = new ReflectionProperty(\Illuminate\Console\Command::class, 'output');
        $prop->setAccessible(true);
        $prop->setValue($command, new BufferedOutput());
    }
}
