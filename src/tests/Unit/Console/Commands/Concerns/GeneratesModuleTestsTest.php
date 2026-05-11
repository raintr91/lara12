<?php

namespace Tests\Unit\Console\Commands\Concerns;

use App\Console\Commands\Concerns\GeneratesModuleFiles;
use App\Console\Commands\Concerns\GeneratesModuleTests;
use Illuminate\Filesystem\Filesystem;
use ReflectionMethod;
use Tests\TestCase;

class GeneratesModuleTestsTest extends TestCase
{
    private array $tmpModules = [];
    private array $tmpFiles = [];

    protected function tearDown(): void
    {
        $files = new Filesystem();

        foreach ($this->tmpModules as $module) {
            $files->deleteDirectory(base_path("Modules/{$module}"));
        }

        foreach ($this->tmpFiles as $path) {
            if ($files->exists($path)) {
                $files->delete($path);
            }
        }

        parent::tearDown();
    }

    public function test_test_generation_config_returns_null_for_unknown_type(): void
    {
        $subject = $this->makeSubject();

        $method = new ReflectionMethod($subject, 'testGenerationConfig');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($subject, 'Hook', 'unknown', 'DemoController'));
    }

    public function test_test_generation_config_supports_controller_layer_unit(): void
    {
        $subject = $this->makeSubject('unit');

        $method = new ReflectionMethod($subject, 'testGenerationConfig');
        $method->setAccessible(true);

        $config = $method->invoke($subject, 'Hook', 'controller', 'DemoController');

        $this->assertIsArray($config);
        $this->assertStringContainsString('Tests\\Unit\\Http\\Controllers', $config['test_namespace']);
        $this->assertStringContainsString('Modules/Hook/Http/Controllers/DemoController.php', $config['target_relative_path']);
    }

    public function test_ensure_generated_class_test_unknown_type_warns_and_returns(): void
    {
        $subject = $this->makeSubject();
        $files = new Filesystem();

        $subject->ensureGeneratedClassTestPublic($files, 'Hook', 'unknown', 'DemoController');

        $this->assertNotEmpty($subject->warnings);
        $this->assertStringContainsString('Unknown test generation type', $subject->warnings[0]);
    }

    public function test_ensure_generated_class_test_creates_file_and_handles_existing_file(): void
    {
        $subject = $this->makeSubject();
        $files = new Filesystem();
        $module = 'TmpGenTest' . uniqid();
        $this->tmpModules[] = $module;

        $subject->ensureGeneratedClassTestPublic($files, $module, 'resource', 'DemoResource', false);
        $this->assertNotEmpty($subject->lines);

        $testPath = base_path("Modules/{$module}/Tests/Unit/Http/Resources/DemoResourceTest.php");
        $this->assertFileExists($testPath);

        $subject->ensureGeneratedClassTestPublic($files, $module, 'resource', 'DemoResource', false);
        $this->assertStringContainsString('File already exists', implode("\n", $subject->lines));
    }

    public function test_ensure_module_testsuite_and_directories_warns_when_marker_missing(): void
    {
        $subject = $this->makeSubject();
        $files = new Filesystem();

        $phpunitPath = base_path('phpunit.xml');
        $backupPath = $phpunitPath . '.bak.' . uniqid();
        $this->tmpFiles[] = $backupPath;
        $files->copy($phpunitPath, $backupPath);

        try {
            $files->put($phpunitPath, "<phpunit><testsuites></testsuites-missing></phpunit>");
            $subject->ensureModuleTestsuiteAndDirectoriesPublic($files, 'TmpMarker' . uniqid());
            $this->assertStringContainsString('Cannot locate </testsuites>', implode("\n", $subject->warnings));
        } finally {
            $files->move($backupPath, $phpunitPath);
        }
    }

    private function makeSubject(string $controllerLayer = 'feature')
    {
        return new class($controllerLayer) {
            use GeneratesModuleFiles;
            use GeneratesModuleTests;

            public array $warnings = [];
            public array $lines = [];

            public function __construct(private string $layer)
            {
            }

            protected function controllerTestLayer(): string
            {
                return $this->layer;
            }

            public function warn($string, $verbosity = null): void
            {
                $this->warnings[] = (string) $string;
            }

            public function line($string, $verbosity = null): void
            {
                $this->lines[] = (string) $string;
            }

            public function ensureGeneratedClassTestPublic(Filesystem $files, string $module, string $type, string $className, bool $force = false): void
            {
                $this->ensureGeneratedClassTest($files, $module, $type, $className, $force);
            }

            public function ensureModuleTestsuiteAndDirectoriesPublic(Filesystem $files, string $module): void
            {
                $this->ensureModuleTestsuiteAndDirectories($files, $module);
            }
        };
    }
}
