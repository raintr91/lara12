<?php

namespace Tests\Unit\Http\Queries;

use App\Http\Queries\BaseQuery;
use App\Http\Requests\SearchRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Mockery;
use Tests\Unit\UnitTestCase;

class BaseQueryPaginateAllTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_paginate_returns_all_rows_when_all_flag_set(): void
    {
        $request = new class extends SearchRequest {
        };
        $request->replace(['all' => '1']);

        $builder = Mockery::mock(Builder::class);
        $collection = collect([1, 2]);
        $builder->shouldReceive('get')->once()->andReturn($collection);

        $query = new class($request, $builder) extends BaseQuery {
            public function __construct(SearchRequest $request, private Builder $builder)
            {
                parent::__construct($request);
            }

            protected function newQuery(): Builder
            {
                return $this->builder;
            }
        };

        $this->assertSame($collection, $query->paginate());
    }
}
