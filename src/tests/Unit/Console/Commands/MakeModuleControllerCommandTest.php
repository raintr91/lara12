<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\MakeModuleControllerCommand;
use Illuminate\Filesystem\Filesystem;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Input\ArrayInput;
use Tests\TestCase;

class MakeModuleControllerCommandTest extends TestCase
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

    public function test_ask_actions_to_wire_non_interactive_defaults_from_existing_files(): void
    {
        $command = new class extends MakeModuleControllerCommand {
            public function confirm($question, $default = false): bool
            {
                return $default;
            }
        };

        $input = new ArrayInput([]);
        $input->setInteractive(false);
        $inputProp = new ReflectionProperty(\Illuminate\Console\Command::class, 'input');
        $inputProp->setAccessible(true);
        $inputProp->setValue($command, $input);

        $files = $this->mockFiles(true, true);

        $method = new ReflectionMethod(MakeModuleControllerCommand::class, 'askActionsToWire');
        $method->setAccessible(true);

        $actions = $method->invoke($command, $files, '/tmp/action.php', '/tmp/query.php');

        $this->assertSame(['create', 'update', 'delete', 'search', 'detail'], $actions);
    }

    public function test_ask_actions_to_wire_honors_wire_options_when_skip_questions(): void
    {
        $command = new class extends MakeModuleControllerCommand {
            private array $optionValues = [
                'yes' => false,
                'skip-questions' => true,
                'wire-create' => 'no',
                'wire-update' => 'no',
                'wire-delete' => 'yes',
                'wire-search' => 'no',
                'wire-detail' => 'yes',
                'wire-multiple-delete' => 'no',
            ];

            public function hasOption($name): bool
            {
                return array_key_exists($name, $this->optionValues);
            }

            public function option($key = null)
            {
                if ($key === null) {
                    return $this->optionValues;
                }

                return $this->optionValues[$key] ?? null;
            }
        };

        $files = $this->mockFiles(true, true);

        $method = new ReflectionMethod(MakeModuleControllerCommand::class, 'askActionsToWire');
        $method->setAccessible(true);

        $actions = $method->invoke($command, $files, '/tmp/action.php', '/tmp/query.php');

        $this->assertSame(['delete', 'detail'], $actions);
    }

    public function test_ask_actions_to_wire_honors_wire_multiple_delete_option(): void
    {
        $command = new class extends MakeModuleControllerCommand {
            private array $optionValues = [
                'yes' => false,
                'skip-questions' => true,
                'wire-create' => 'no',
                'wire-update' => 'no',
                'wire-delete' => 'no',
                'wire-search' => 'no',
                'wire-detail' => 'no',
                'wire-multiple-delete' => 'yes',
            ];

            public function hasOption($name): bool
            {
                return array_key_exists($name, $this->optionValues);
            }

            public function option($key = null)
            {
                if ($key === null) {
                    return $this->optionValues;
                }

                return $this->optionValues[$key] ?? null;
            }
        };

        $files = $this->mockFiles(true, false);

        $method = new ReflectionMethod(MakeModuleControllerCommand::class, 'askActionsToWire');
        $method->setAccessible(true);

        $actions = $method->invoke($command, $files, '/tmp/action.php', '/tmp/query.php');

        $this->assertSame(['bulk-delete'], $actions);
    }

    public function test_ask_actions_to_wire_interactive_uses_confirm_answers(): void
    {
        $command = new class extends MakeModuleControllerCommand {
            private array $answers = [true, false, true, false, true, false];

            public function confirm($question, $default = false): bool
            {
                return array_shift($this->answers);
            }
        };

        $input = new ArrayInput([]);
        $input->setInteractive(true);
        $inputProp = new ReflectionProperty(\Illuminate\Console\Command::class, 'input');
        $inputProp->setAccessible(true);
        $inputProp->setValue($command, $input);

        $files = $this->mockFiles(true, true);

        $method = new ReflectionMethod(MakeModuleControllerCommand::class, 'askActionsToWire');
        $method->setAccessible(true);

        $actions = $method->invoke($command, $files, '/tmp/action.php', '/tmp/query.php');

        $this->assertSame(['create', 'delete', 'detail'], $actions);
    }

    public function test_handle_returns_error_for_invalid_controller_test_layer(): void
    {
        $this->artisan('m:controller', [
            'module' => 'Hook',
            'name' => 'TmpInvalidLayer' . uniqid(),
            '--controller-test-layer' => 'invalid',
            '--skip-questions' => true,
        ])->assertExitCode(1);
    }

    public function test_handle_creates_controller_with_non_interactive_defaults(): void
    {
        $module = 'TmpController' . uniqid();
        $this->tmpModules[] = $module;

        $this->artisan('m:controller', [
            'module' => $module,
            'name' => 'Sample',
            '--create-request' => 'no',
            '--search-request' => 'no',
            '--action-class' => 'no',
            '--query-class' => 'no',
            '--resource-class' => 'no',
            '--shared-model' => 'no',
            '--wire-create' => 'no',
            '--wire-update' => 'no',
            '--wire-delete' => 'no',
            '--wire-search' => 'no',
            '--wire-detail' => 'no',
            '--wire-multiple-delete' => 'no',
            '--select-items' => 'no',
            '--skip-questions' => true,
            '--controller-test-layer' => 'unit',
        ])->assertExitCode(0);

        $controllerPath = base_path("Modules/{$module}/Http/Controllers/SampleController.php");
        $this->assertFileExists($controllerPath);
    }

    public function test_write_controller_skeleton_fallback_without_stub(): void
    {
        $command = new MakeModuleControllerCommand();
        $files = new Filesystem();

        $method = new ReflectionMethod(MakeModuleControllerCommand::class, 'writeControllerSkeleton');
        $method->setAccessible(true);

        $module = 'TmpSkeleton' . uniqid();
        $this->tmpModules[] = $module;
        $controllerPath = base_path("Modules/{$module}/Http/Controllers/DemoController.php");

        $stubPath = base_path('stubs/modules/scaffold/module-controller.stub');
        $backupPath = $stubPath . '.bak.' . uniqid();
        $hadStub = $files->exists($stubPath);
        if ($hadStub) {
            $files->move($stubPath, $backupPath);
        }

        try {
            $method->invoke($command, $files, $module, $module . 'Controller', 'DemoController', $controllerPath);
            $contents = $files->get($controllerPath);
            $this->assertStringContainsString("class DemoController extends {$module}Controller", $contents);
        } finally {
            if ($hadStub && $files->exists($backupPath)) {
                $files->move($backupPath, $stubPath);
            }
        }
    }

    public function test_handle_keeps_existing_controller_when_overwrite_is_no(): void
    {
        $module = 'TmpKeep' . uniqid();
        $this->tmpModules[] = $module;
        $files = new Filesystem();

        $controllerPath = base_path("Modules/{$module}/Http/Controllers/SampleController.php");
        $files->makeDirectory(dirname($controllerPath), 0775, true);
        $files->put($controllerPath, "<?php\nclass SampleController {}\n");

        $this->artisan('m:controller', [
            'module' => $module,
            'name' => 'Sample',
            '--create-request' => 'no',
            '--search-request' => 'no',
            '--action-class' => 'no',
            '--query-class' => 'no',
            '--resource-class' => 'no',
            '--shared-model' => 'no',
            '--overwrite-controller' => 'no',
            '--wire-create' => 'no',
            '--wire-update' => 'no',
            '--wire-delete' => 'no',
            '--wire-search' => 'no',
            '--wire-detail' => 'no',
            '--wire-multiple-delete' => 'no',
            '--select-items' => 'no',
            '--skip-questions' => true,
        ])->assertExitCode(0);

        $this->assertStringContainsString('class SampleController {}', $files->get($controllerPath));
    }

    public function test_controller_test_layer_accessor_returns_configured_value(): void
    {
        $command = new MakeModuleControllerCommand();

        $prop = new ReflectionProperty(MakeModuleControllerCommand::class, 'controllerLayer');
        $prop->setAccessible(true);
        $prop->setValue($command, 'unit');

        $method = new ReflectionMethod(MakeModuleControllerCommand::class, 'controllerTestLayer');
        $method->setAccessible(true);

        $this->assertSame('unit', $method->invoke($command));
    }

    private function mockFiles(bool $hasAction, bool $hasQuery): Filesystem
    {
        return new class($hasAction, $hasQuery) extends Filesystem {
            private bool $hasAction;
            private bool $hasQuery;

            public function __construct(bool $hasAction, bool $hasQuery)
            {
                $this->hasAction = $hasAction;
                $this->hasQuery = $hasQuery;
            }

            public function exists($path)
            {
                if (str_contains($path, 'action')) {
                    return $this->hasAction;
                }

                if (str_contains($path, 'query')) {
                    return $this->hasQuery;
                }

                return false;
            }
        };
    }
}
