<?php

namespace Modules\Chain\Tests\Unit\Http\Actions;

use ReflectionClass;
use Modules\Chain\Http\Actions\HotelAction;
use Tests\TestCase;

class HotelActionTest extends TestCase
{
    public function test_target_file_exists(): void
    {
        $this->assertFileExists(base_path('Modules/Chain/Http/Actions/HotelAction.php'));
    }

    public function test_target_class_declaration_matches_expected_fqcn(): void
    {
        $path = base_path('Modules/Chain/Http/Actions/HotelAction.php');
        $contents = file_get_contents($path);

        $this->assertNotFalse($contents, "Unable to read [{$path}].");

        preg_match('/^namespace\s+([^;]+);/m', $contents, $namespaceMatches);
        preg_match('/^\s*(?:abstract\s+|final\s+)?(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $contents, $classMatches);

        $this->assertArrayHasKey(1, $namespaceMatches, "No namespace declaration found in [{$path}].");
        $this->assertArrayHasKey(1, $classMatches, "No class-like declaration found in [{$path}].");

        $declaredFqcn = trim($namespaceMatches[1]).'\\'.trim($classMatches[1]);
        $this->assertSame('Modules\Chain\Http\Actions\HotelAction', $declaredFqcn);
    }

    public function test_action_class_is_loadable(): void
    {
        $this->assertTrue(class_exists(\Modules\Chain\Http\Actions\HotelAction::class));
    }

    public function test_action_extends_module_base_action(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\Chain\Http\Actions\HotelAction::class, \App\Http\Actions\BaseAction::class));
    }
}
