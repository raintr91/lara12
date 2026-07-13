<?php

namespace Modules\Chain\Tests\Unit\Http\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use ReflectionClass;
use Modules\Chain\Http\Queries\HotelQuery;
use Tests\TestCase;

class HotelQueryTest extends TestCase
{
    public function test_target_file_exists(): void
    {
        $this->assertFileExists(base_path('Modules/Chain/Http/Queries/HotelQuery.php'));
    }

    public function test_target_class_declaration_matches_expected_fqcn(): void
    {
        $path = base_path('Modules/Chain/Http/Queries/HotelQuery.php');
        $contents = file_get_contents($path);

        $this->assertNotFalse($contents, "Unable to read [{$path}].");

        preg_match('/^namespace\s+([^;]+);/m', $contents, $namespaceMatches);
        preg_match('/^\s*(?:abstract\s+|final\s+)?(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $contents, $classMatches);

        $this->assertArrayHasKey(1, $namespaceMatches, "No namespace declaration found in [{$path}].");
        $this->assertArrayHasKey(1, $classMatches, "No class-like declaration found in [{$path}].");

        $declaredFqcn = trim($namespaceMatches[1]).'\\'.trim($classMatches[1]);
        $this->assertSame('Modules\Chain\Http\Queries\HotelQuery', $declaredFqcn);
    }

    public function test_query_class_is_loadable(): void
    {
        $this->assertTrue(class_exists(\Modules\Chain\Http\Queries\HotelQuery::class));
    }

    public function test_query_extends_module_base_query(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\Chain\Http\Queries\HotelQuery::class, \App\Http\Queries\BaseQuery::class));
    }

    public function test_query_construction_path_is_exercised(): void
    {
        $reflection = new ReflectionClass(\Modules\Chain\Http\Queries\HotelQuery::class);

        if ($reflection->isAbstract()) {
            $query = new class (Request::create('/admin-test', 'GET')) extends \Modules\Chain\Http\Queries\HotelQuery {
                protected function newQuery(): Builder
                {
                    return (new class extends Model {
                        protected $table = 'users';
                        public $timestamps = false;
                    })->newQuery();
                }
            };

            $this->assertInstanceOf(\Modules\Chain\Http\Queries\HotelQuery::class, $query);

            return;
        }

        $query = new \Modules\Chain\Http\Queries\HotelQuery(Request::create('/admin-test', 'GET'));

        $this->assertInstanceOf(\Modules\Chain\Http\Queries\HotelQuery::class, $query);
    }
}
