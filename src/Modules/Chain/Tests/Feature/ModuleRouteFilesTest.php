<?php

namespace Modules\Chain\Tests\Feature;

use Tests\TestCase;

class ModuleRouteFilesTest extends TestCase
{
    public function test_module_route_files_exist(): void
    {
        $this->assertFileExists(module_path('Chain', 'Routes/api.php'));

        $authPath = module_path('Chain', 'Routes/auth.php');
        if (is_file($authPath)) {
            $this->assertFileExists($authPath);
        }
    }
}
