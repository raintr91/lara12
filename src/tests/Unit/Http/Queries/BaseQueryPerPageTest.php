<?php

namespace Tests\Unit\Http\Queries;

use App\Http\Queries\BaseQuery;
use App\Http\Requests\SearchRequest;
use Illuminate\Database\Eloquent\Builder;
use Mockery;
use Tests\Unit\UnitTestCase;

class BaseQueryPerPageTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_uses_request_max_per_page(): void
    {
        $request = new class extends SearchRequest {
            protected int $maxPerPage = 25;
        };
        $request->replace(['per_page' => 99]);

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

            public function exposesPerPage(): int
            {
                return $this->perPage();
            }
        };

        $this->assertSame(25, $query->exposesPerPage());
    }
}
