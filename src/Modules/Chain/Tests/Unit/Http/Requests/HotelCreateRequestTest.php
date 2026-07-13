<?php

namespace Modules\Chain\Tests\Unit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use ReflectionClass;
use \Modules\Chain\Http\Requests\HotelCreateRequest;
use Modules\Chain\Tests\Support\ModuleTestSupport;
use Tests\Unit\UnitTestCase;

class HotelCreateRequestTest extends UnitTestCase
{
    use ModuleTestSupport;

    public function test_target_file_exists(): void
    {
        $this->assertFileExists(base_path('Modules/Chain/Http/Requests/HotelCreateRequest.php'));
    }

    public function test_target_class_declaration_matches_expected_fqcn(): void
    {
        $path = base_path('Modules/Chain/Http/Requests/HotelCreateRequest.php');
        $contents = file_get_contents($path);

        $this->assertNotFalse($contents, "Unable to read [{$path}].");

        preg_match('/^namespace\s+([^;]+);/m', $contents, $namespaceMatches);
        preg_match('/^\s*(?:abstract\s+|final\s+)?(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $contents, $classMatches);

        $this->assertArrayHasKey(1, $namespaceMatches, "No namespace declaration found in [{$path}].");
        $this->assertArrayHasKey(1, $classMatches, "No class-like declaration found in [{$path}].");

        $declaredFqcn = trim($namespaceMatches[1]).'\\'.trim($classMatches[1]);
        $this->assertSame('Modules\Chain\Http\Requests\HotelCreateRequest', $declaredFqcn);
    }

    public function test_request_class_is_loadable(): void
    {
        $this->assertTrue(class_exists(\Modules\Chain\Http\Requests\HotelCreateRequest::class));
    }

    public function test_request_extends_form_request(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\Chain\Http\Requests\HotelCreateRequest::class, FormRequest::class));
    }

    public function test_request_can_be_instantiated(): void
    {
        $reflection = new ReflectionClass(\Modules\Chain\Http\Requests\HotelCreateRequest::class);

        if ($reflection->isAbstract()) {
            $request = new class extends \Modules\Chain\Http\Requests\HotelCreateRequest {};
        } else {
            $request = new \Modules\Chain\Http\Requests\HotelCreateRequest();
        }

        $this->assertTrue($request->authorize());
        $this->assertIsArray($request->rules());
    }

    public function test_validation_hooks(): void
    {
        $request = $this->makeSearchRequestInstance(\Modules\Chain\Http\Requests\HotelCreateRequest::class);
        $this->exerciseRequestValidationHooks($request);
    }
}
