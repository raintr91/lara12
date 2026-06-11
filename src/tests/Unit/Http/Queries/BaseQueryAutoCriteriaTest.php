<?php

namespace Tests\Unit\Http\Queries;

use App\Http\Queries\BaseQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Mockery;
use Tests\Unit\Support\SampleAutoCriteria;
use Tests\Unit\UnitTestCase;

class BaseQueryAutoCriteriaTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_auto_applies_criteria_class(): void
    {
        SampleAutoCriteria::$applied = false;

        $request = new Request();
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('get')->once()->andReturn(collect());

        $query = new class($request, $builder) extends BaseQuery {
            public function __construct(Request $request, private Builder $builder)
            {
                parent::__construct($request);
            }

            protected function newQuery(): Builder
            {
                return $this->builder;
            }

            protected function criteriaClass(): ?string
            {
                return SampleAutoCriteria::class;
            }
        };

        $query->get();
        $this->assertTrue(SampleAutoCriteria::$applied);
    }
}
