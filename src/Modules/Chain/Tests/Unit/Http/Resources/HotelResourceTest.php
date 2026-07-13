<?php

namespace Modules\Chain\Tests\Unit\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use ReflectionClass;
use Modules\Chain\Http\Resources\HotelResource;
use Tests\TestCase;

class HotelResourceTest extends TestCase
{
    public function test_target_file_exists(): void
    {
        $this->assertFileExists(base_path('Modules/Chain/Http/Resources/HotelResource.php'));
    }

    public function test_target_class_declaration_matches_expected_fqcn(): void
    {
        $path = base_path('Modules/Chain/Http/Resources/HotelResource.php');
        $contents = file_get_contents($path);

        $this->assertNotFalse($contents, "Unable to read [{$path}].");

        preg_match('/^namespace\s+([^;]+);/m', $contents, $namespaceMatches);
        preg_match('/^\s*(?:abstract\s+|final\s+)?(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $contents, $classMatches);

        $this->assertArrayHasKey(1, $namespaceMatches, "No namespace declaration found in [{$path}].");
        $this->assertArrayHasKey(1, $classMatches, "No class-like declaration found in [{$path}].");

        $declaredFqcn = trim($namespaceMatches[1]).'\\'.trim($classMatches[1]);
        $this->assertSame('Modules\Chain\Http\Resources\HotelResource', $declaredFqcn);
    }

    public function test_resource_class_is_loadable(): void
    {
        $this->assertTrue(class_exists(\Modules\Chain\Http\Resources\HotelResource::class));
    }

    public function test_resource_extends_json_resource(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\Chain\Http\Resources\HotelResource::class, JsonResource::class));
    }

    public function test_resource_can_transform_model_to_array(): void
    {
        $model = new class extends Model {
            protected $guarded = [];
            public $timestamps = false;
        };

        $model->forceFill(['id' => 123]);

        $reflection = new ReflectionClass(\Modules\Chain\Http\Resources\HotelResource::class);

        if ($reflection->isAbstract()) {
            $resource = new class ($model) extends \Modules\Chain\Http\Resources\HotelResource {};
        } else {
            $resource = new \Modules\Chain\Http\Resources\HotelResource($model);
        }

        $data = $resource->toArray(Request::create('/admin-test', 'GET'));

        $this->assertSame(123, $data['id']);
    }
}
