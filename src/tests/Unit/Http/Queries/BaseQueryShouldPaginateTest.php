<?php

namespace Tests\Unit\Http\Queries;

use App\Http\Queries\BaseQuery;
use App\Http\Requests\SearchRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Mockery;
use Tests\Unit\UnitTestCase;

class BaseQueryShouldPaginateTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_honors_search_request_all_flag(): void
    {
        $request = new class extends SearchRequest {
        };
        $request->replace(['all' => true]);

        $builder = Mockery::mock(Builder::class);
        $query = new class($request, $builder) extends BaseQuery {
            public function __construct(SearchRequest $request, private Builder $builder)
            {
                parent::__construct($request);
            }

            protected function newQuery(): Builder
            {
                return $this->builder;
            }

            public function exposesShouldPaginate(): bool
            {
                return $this->shouldPaginate();
            }
        };

        $this->assertFalse($query->exposesShouldPaginate());
    }

    public function test_false_for_plain_request_with_all_flag(): void
    {
        $request = new Request(['all' => '1']);
        $builder = Mockery::mock(Builder::class);
        $query = new class($request, $builder) extends BaseQuery {
            public function __construct(Request $request, private Builder $builder)
            {
                parent::__construct($request);
            }

            protected function newQuery(): Builder
            {
                return $this->builder;
            }

            public function exposesShouldPaginate(): bool
            {
                return $this->shouldPaginate();
            }
        };

        $this->assertFalse($query->exposesShouldPaginate());
    }
}
