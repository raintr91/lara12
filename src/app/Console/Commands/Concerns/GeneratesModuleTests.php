<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

trait GeneratesModuleTests
{
    protected function controllerTestLayer(): string
    {
        return 'feature';
    }

    protected function ensureGeneratedClassTest(
        Filesystem $files,
        string $module,
        string $type,
        string $className,
        bool $force = false
    ): void {
        $module = $this->studly($module);
        $className = $this->studly($className);

        $config = $this->testGenerationConfig($module, $type, $className);
        if ($config === null) {
            $this->warn("Unknown test generation type [{$type}] for [{$className}].");
            return;
        }

        $this->ensureModuleTestsuiteAndDirectories($files, $module);

        $contents = $this->renderStub($files, $config['stub_path'], [
            'TEST_NAMESPACE' => $config['test_namespace'],
            'TEST_CLASS' => $config['test_class'],
            'TARGET_FQCN' => $config['target_fqcn'],
            'TARGET_BASE_FQCN' => $config['target_base_fqcn'],
            'TARGET_BASE_CLASS' => $config['target_base_class'],
            'TARGET_RELATIVE_PATH' => $config['target_relative_path'],
            'TARGET_CLASS' => $className,
        ]);

        try {
            $this->putFile($files, $config['test_path'], $contents, $force);
        } catch (RuntimeException $e) {
            $this->line($e->getMessage());
            return;
        }

        $this->line("Created test: {$config['test_path']}");
    }

    protected function ensureModuleTestsuiteAndDirectories(Filesystem $files, string $module): void
    {
        $moduleRoot = $this->moduleRoot($module);
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

        $phpunitPath = base_path('phpunit.xml');
        if (! $files->exists($phpunitPath)) {
            $this->warn("phpunit.xml not found: {$phpunitPath}");
            return;
        }

        $contents = $files->get($phpunitPath);
        $suiteName = 'Module'.$module;
        if (str_contains($contents, 'testsuite name="'.$suiteName.'"')) {
            return;
        }

        $suiteXml = "\n        <testsuite name=\"{$suiteName}\">\n            <directory suffix=\"Test.php\">Modules/{$module}/Tests</directory>\n        </testsuite>";
        $marker = "\n    </testsuites>";

        if (! str_contains($contents, $marker)) {
            $this->warn('Cannot locate </testsuites> in phpunit.xml; skipped testsuite insertion.');
            return;
        }

        $updated = str_replace($marker, $suiteXml.$marker, $contents);
        if ($updated !== $contents) {
            $files->put($phpunitPath, $updated);
            $this->line("Registered testsuite [{$suiteName}] in phpunit.xml");
        }
    }

    private function testGenerationConfig(string $module, string $type, string $className): ?array
    {
        $controllerLayer = strtolower($this->controllerTestLayer());
        $controllerTestSuffix = $controllerLayer === 'unit'
            ? 'Tests\\Unit\\Http\\Controllers'
            : 'Tests\\Feature\\Http\\Controllers';

        $types = [
            'controller' => [
                'prod_suffix' => 'Http\\Controllers',
                'test_suffix' => $controllerTestSuffix,
                'stub_path' => base_path('stubs/modules/tests/controller-test.stub'),
                'base_suffix' => 'Controller',
            ],
            'action' => [
                'prod_suffix' => 'Http\\Actions',
                'test_suffix' => 'Tests\\Unit\\Http\\Actions',
                'stub_path' => base_path('stubs/modules/tests/action-test.stub'),
                'base_suffix' => 'Action',
            ],
            'query' => [
                'prod_suffix' => 'Http\\Queries',
                'test_suffix' => 'Tests\\Unit\\Http\\Queries',
                'stub_path' => base_path('stubs/modules/tests/query-test.stub'),
                'base_suffix' => 'Query',
            ],
            'request' => [
                'prod_suffix' => 'Http\\Requests',
                'test_suffix' => 'Tests\\Unit\\Http\\Requests',
                'stub_path' => base_path('stubs/modules/tests/request-test.stub'),
                'base_suffix' => 'Request',
            ],
            'resource' => [
                'prod_suffix' => 'Http\\Resources',
                'test_suffix' => 'Tests\\Unit\\Http\\Resources',
                'stub_path' => base_path('stubs/modules/tests/resource-test.stub'),
                'base_suffix' => 'Resource',
            ],
        ];

        if (! isset($types[$type])) {
            return null;
        }

        $config = $types[$type];

        $targetFqcn = $this->moduleNamespace($module, $config['prod_suffix']).'\\'.$className;
        $targetBaseFqcn = $this->moduleNamespace($module, $config['prod_suffix']).'\\'.$module.$config['base_suffix'];
        $testNamespace = $this->moduleNamespace($module, $config['test_suffix']);
        $testClass = $className.'Test';
        $targetRelativePath = 'Modules/'.$module.'/'.str_replace('\\', '/', $config['prod_suffix']).'/'.$className.'.php';
        $testPath = $this->moduleRoot($module).'/'.str_replace('\\', '/', $config['test_suffix']).'/'.$testClass.'.php';

        return [
            'stub_path' => $config['stub_path'],
            'target_fqcn' => $targetFqcn,
            'target_base_fqcn' => $targetBaseFqcn,
            'target_base_class' => $module.$config['base_suffix'],
            'target_relative_path' => $targetRelativePath,
            'test_namespace' => $testNamespace,
            'test_class' => $testClass,
            'test_path' => $testPath,
        ];
    }
}
