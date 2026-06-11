<?php

namespace Tests\Unit\Http\Controllers\Traits;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Traits\EntryDetailTrait;
use App\Http\Controllers\Traits\EntrySearchTrait;
use App\Http\Queries\BaseQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Mockery;
use Tests\Unit\UnitTestCase;

class EntryReadTraitsTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_search_success(): void
    {
        $controller = new class extends BaseController {
            use EntrySearchTrait;

            public BaseQuery $query;
        };

        $controller->query = Mockery::mock(BaseQuery::class);
        $controller->query->shouldReceive('paginate')->once()->andReturn(
            new Paginator([['id' => 1]], 1, 15, 1)
        );

        $response = $controller->search();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame([['id' => 1]], $data['data']);
        $this->assertSame(1, $data['meta']['pagination']['total']);
    }

    public function test_search_error(): void
    {
        $controller = new class extends BaseController {
            use EntrySearchTrait;

            public BaseQuery $query;
        };

        $controller->query = Mockery::mock(BaseQuery::class);
        $controller->query->shouldReceive('paginate')->once()->andThrow(new \RuntimeException('search failed'));

        $response = $controller->search();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertSame('search failed', $data['message']);
    }

    public function test_get_detail_success(): void
    {
        $controller = new class extends BaseController {
            use EntryDetailTrait;

            public BaseQuery $query;
        };

        $controller->query = Mockery::mock(BaseQuery::class);
        $controller->query->shouldReceive('findById')->once()->with(8)->andReturn(['id' => 8, 'name' => 'robot']);

        $response = $controller->getDetail(8);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(8, $data['data']['id']);
    }

    public function test_get_detail_error(): void
    {
        $controller = new class extends BaseController {
            use EntryDetailTrait;

            public BaseQuery $query;
        };

        $controller->query = Mockery::mock(BaseQuery::class);
        $controller->query->shouldReceive('findById')->once()->andThrow(new \RuntimeException('detail failed'));

        $response = $controller->getDetail(1);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function test_get_detail_not_found(): void
    {
        $controller = new class extends BaseController {
            use EntryDetailTrait;

            public BaseQuery $query;
        };

        $controller->query = Mockery::mock(BaseQuery::class);
        $controller->query->shouldReceive('findById')->once()->with(8)->andReturn(null);

        $response = $controller->getDetail(8);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertSame('Resource not found', $data['message']);
    }
}
