<?php

namespace Tests\Unit\Http\Queries\Traits;

use App\Http\Queries\BaseQuery;
use App\Http\Queries\Traits\ProvidesEntityStats;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Mockery;
use Tests\Unit\UnitTestCase;

class ProvidesEntityStatsTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_counts_rows(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('count')->once()->andReturn(3);

        $query = new class(Request::create('/test', 'GET'), $builder) extends BaseQuery {
            use ProvidesEntityStats;

            public function __construct(Request $request, private Builder $builder)
            {
                parent::__construct($request);
            }

            protected function statsKey(): string
            {
                return 'accounts';
            }

            protected function newQuery(): Builder
            {
                return $this->builder;
            }
        };

        $this->assertSame(['accounts' => 3], $query->stats());
    }

    public function test_returns_zero_when_count_is_zero(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('count')->once()->andReturn(0);

        $query = new class(Request::create('/test', 'GET'), $builder) extends BaseQuery {
            use ProvidesEntityStats;

            public function __construct(Request $request, private Builder $builder)
            {
                parent::__construct($request);
            }

            protected function statsKey(): string
            {
                return 'items';
            }

            protected function newQuery(): Builder
            {
                return $this->builder;
            }
        };

        $this->assertSame(['items' => 0], $query->stats());
    }
}
