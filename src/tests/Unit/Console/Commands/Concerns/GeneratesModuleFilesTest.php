<?php

namespace Tests\Unit\Console\Commands\Concerns;

use App\Console\Commands\Concerns\GeneratesModuleFiles;
use Illuminate\Filesystem\Filesystem;
use ReflectionMethod;
use Tests\TestCase;

class GeneratesModuleFilesTest extends TestCase
{
    private function helper()
    {
        return new class {
            use GeneratesModuleFiles;

            public function studlyPublic(string $value): string
            {
                return $this->studly($value);
            }

            public function studlyWithSuffixPublic(string $value, string $suffix): string
            {
                return $this->studlyWithSuffix($value, $suffix);
            }

            public function moduleNamespacePublic(string $module, string $suffix = ''): string
            {
                return $this->moduleNamespace($module, $suffix);
            }

            public function moduleRootPublic(string $module): string
            {
                return $this->moduleRoot($module);
            }

            public function ensureModuleExistsPublic(Filesystem $files, string $module): bool
            {
                return $this->ensureModuleExists($files, $module);
            }

            public function renderStubPublic(Filesystem $files, string $stubPath, array $replacements): string
            {
                return $this->renderStub($files, $stubPath, $replacements);
            }

            public function putFilePublic(Filesystem $files, string $path, string $contents, bool $force = false): void
            {
                $this->putFile($files, $path, $contents, $force);
            }
        };
    }

    public function test_studly_with_suffix_handles_existing_suffix_case_insensitive(): void
    {
        $helper = $this->helper();

        $this->assertSame('UserName', $helper->studlyPublic('user_name'));
        $this->assertSame('UserRequest', $helper->studlyWithSuffixPublic('userrequest', 'Request'));
        $this->assertSame('UserRequest', $helper->studlyWithSuffixPublic('user', 'Request'));
        $this->assertSame('User', $helper->studlyWithSuffixPublic('user', ''));
    }

    public function test_module_namespace_builds_with_suffix(): void
    {
        $helper = $this->helper();

        $this->assertSame('Modules\\Admin\\Http\\Requests', $helper->moduleNamespacePublic('Admin', 'Http\\Requests'));
    }

    public function test_render_stub_replaces_placeholders(): void
    {
        $helper = $this->helper();
        $files = new Filesystem();

        $tmp = storage_path('framework/cache/test-stub-' . uniqid() . '.stub');
        $files->put($tmp, 'Hello $NAME$ in $MODULE$');

        $rendered = $helper->renderStubPublic($files, $tmp, [
            'name' => 'User',
            'module' => 'Admin',
        ]);

        $this->assertSame('Hello User in Admin', $rendered);

        $files->delete($tmp);
    }

    public function test_module_root_and_ensure_module_exists(): void
    {
        $helper = $this->helper();
        $files = new Filesystem();

        $module = 'TmpGen' . uniqid();
        $root = base_path('Modules/' . $module);

        $this->assertSame($root, $helper->moduleRootPublic($module));
        $this->assertFalse($helper->ensureModuleExistsPublic($files, $module));

        $files->makeDirectory($root, 0775, true);
        $this->assertTrue($helper->ensureModuleExistsPublic($files, $module));

        $files->deleteDirectory($root);
    }

    public function test_put_file_creates_directory_and_respects_force_flag(): void
    {
        $helper = $this->helper();
        $files = new Filesystem();

        $dir = storage_path('framework/cache/gen-files-' . uniqid());
        $path = $dir . '/demo.txt';

        $helper->putFilePublic($files, $path, 'first');
        $this->assertSame('first', $files->get($path));

        $this->expectException(\RuntimeException::class);
        $helper->putFilePublic($files, $path, 'second');
    }

    public function test_put_file_force_overwrites_existing_file(): void
    {
        $helper = $this->helper();
        $files = new Filesystem();

        $dir = storage_path('framework/cache/gen-files-force-' . uniqid());
        $path = $dir . '/demo.txt';

        $helper->putFilePublic($files, $path, 'first');
        $helper->putFilePublic($files, $path, 'second', true);

        $this->assertSame('second', $files->get($path));

        $files->deleteDirectory($dir);
    }
}
