<?php

namespace Tests\Unit\Http\Controllers\Traits;

use App\Http\Actions\BaseAction;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Traits\EntryQueryExecutionTrait;
use App\Http\Queries\BaseQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Mockery;
use Tests\TestCase;

class EntryQueryExecutionTraitTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_execute_action(): void
    {
        $controller = new class extends BaseController {
            use EntryQueryExecutionTrait;

            public function runExecuteAction(BaseAction $action, Request $request)
            {
                return $this->executeAction($action, $request, 'Done', 201);
            }
        };

        $action = new class extends BaseAction {
            protected function run(...$args)
            {
                return ['ok' => true, 'payload' => $args[0] ?? []];
            }
        };

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validated')->once()->andReturn(['name' => 'x']);

        $response = $controller->runExecuteAction($action, $request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->status());
        $this->assertTrue($data['success']);
        $this->assertSame('Done', $data['message']);
    }

    public function test_execute_query(): void
    {
        $controller = new class extends BaseController {
            use EntryQueryExecutionTrait;

            public function runExecuteQuery(BaseQuery $query)
            {
                return $this->executeQuery($query, 'Listed');
            }
        };

        $query = $this->fakeQueryWith([
            'paginate' => new Paginator([['id' => 1]], 1, 15, 1),
        ]);

        $response = $controller->runExecuteQuery($query);
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertSame('Listed', $data['message']);
    }

    public function test_execute_query_all(): void
    {
        $controller = new class extends BaseController {
            use EntryQueryExecutionTrait;

            public function runExecuteQueryAll(BaseQuery $query)
            {
                return $this->executeQueryAll($query, 'All');
            }
        };

        $query = $this->fakeQueryWith([
            'all' => [['id' => 1], ['id' => 2]],
        ]);

        $response = $controller->runExecuteQueryAll($query);
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertSame('All', $data['message']);
        $this->assertCount(2, $data['data']);
    }

    public function test_execute_query_single_found(): void
    {
        $controller = new class extends BaseController {
            use EntryQueryExecutionTrait;

            public function runExecuteQuerySingle(BaseQuery $query)
            {
                return $this->executeQuerySingle($query, 'One');
            }
        };

        $query = $this->fakeQueryWith([
            'first' => ['id' => 5],
        ]);

        $response = $controller->runExecuteQuerySingle($query);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->status());
        $this->assertTrue($data['success']);
        $this->assertSame(5, $data['data']['id']);
    }

    public function test_execute_query_single_not_found(): void
    {
        $controller = new class extends BaseController {
            use EntryQueryExecutionTrait;

            public function runExecuteQuerySingle(BaseQuery $query)
            {
                return $this->executeQuerySingle($query, 'One');
            }
        };

        $query = $this->fakeQueryWith([
            'first' => null,
        ]);

        $response = $controller->runExecuteQuerySingle($query);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(404, $response->status());
        $this->assertFalse($data['success']);
        $this->assertSame('Resource not found', $data['message']);
    }

    public function test_execute_query_select(): void
    {
        $controller = new class extends BaseController {
            use EntryQueryExecutionTrait;

            public function runExecuteQuerySelect(BaseQuery $query)
            {
                return $this->executeQuerySelect($query, ['id', 'name'], 'Select');
            }
        };

        $query = $this->fakeQueryWith([
            'selectItems' => [['id' => 1, 'name' => 'A']],
        ]);

        $response = $controller->runExecuteQuerySelect($query);
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertSame('Select', $data['message']);
        $this->assertSame('A', $data['data'][0]['name']);
    }

    private function fakeQueryWith(array $returns): BaseQuery
    {
        return new class(Request::create('/'), $returns) extends BaseQuery {
            private array $returns;

            public function __construct(Request $request, array $returns)
            {
                $this->returns = $returns;
                parent::__construct($request);
            }

            protected function newQuery(): Builder
            {
                return Mockery::mock(Builder::class);
            }

            public function paginate(): LengthAwarePaginator
            {
                return $this->returns['paginate'] ?? new Paginator([], 0, 15, 1);
            }

            public function all()
            {
                return $this->returns['all'] ?? [];
            }

            public function first()
            {
                return $this->returns['first'] ?? null;
            }

            public function selectItems(array $columns = ['id', 'name'])
            {
                return $this->returns['selectItems'] ?? [];
            }
        };
    }
}
