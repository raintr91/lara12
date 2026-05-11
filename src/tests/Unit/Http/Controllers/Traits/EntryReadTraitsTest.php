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
use Tests\TestCase;

class EntryReadTraitsTest extends TestCase
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
        };

        $query = new class(Request::create('/')) extends BaseQuery {
            protected function newQuery(): Builder
            {
                return Mockery::mock(Builder::class);
            }

            public function paginate(): LengthAwarePaginator
            {
                return new Paginator([['id' => 1]], 1, 15, 1);
            }
        };

        $response = $controller->search($query);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->status());
        $this->assertTrue($data['success']);
        $this->assertSame('Retrieved successfully', $data['message']);
    }

    public function test_search_error(): void
    {
        $controller = new class extends BaseController {
            use EntrySearchTrait;
        };

        $query = new class(Request::create('/')) extends BaseQuery {
            protected function newQuery(): Builder
            {
                return Mockery::mock(Builder::class);
            }

            public function paginate(): LengthAwarePaginator
            {
                throw new \RuntimeException('search failed');
            }
        };

        $response = $controller->search($query);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $response->status());
        $this->assertFalse($data['success']);
        $this->assertSame('search failed', $data['message']);
    }

    public function test_get_detail_success(): void
    {
        $controller = new class extends BaseController {
            use EntryDetailTrait;
        };

        $query = new class(Request::create('/')) extends BaseQuery {
            protected function newQuery(): Builder
            {
                return Mockery::mock(Builder::class);
            }

            public function findById($id)
            {
                return ['id' => $id, 'name' => 'robot'];
            }
        };

        $response = $controller->getDetail($query, 8);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->status());
        $this->assertTrue($data['success']);
        $this->assertSame(8, $data['data']['id']);
    }

    public function test_get_detail_not_found(): void
    {
        $controller = new class extends BaseController {
            use EntryDetailTrait;
        };

        $query = new class(Request::create('/')) extends BaseQuery {
            protected function newQuery(): Builder
            {
                return Mockery::mock(Builder::class);
            }

            public function findById($id)
            {
                return null;
            }
        };

        $response = $controller->getDetail($query, 8);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(404, $response->status());
        $this->assertFalse($data['success']);
        $this->assertSame('Resource not found', $data['message']);
    }

    public function test_get_detail_error(): void
    {
        $controller = new class extends BaseController {
            use EntryDetailTrait;
        };

        $query = new class(Request::create('/')) extends BaseQuery {
            protected function newQuery(): Builder
            {
                return Mockery::mock(Builder::class);
            }

            public function findById($id)
            {
                throw new \RuntimeException('detail failed');
            }
        };

        $response = $controller->getDetail($query, 8);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $response->status());
        $this->assertFalse($data['success']);
        $this->assertSame('detail failed', $data['message']);
    }
}
