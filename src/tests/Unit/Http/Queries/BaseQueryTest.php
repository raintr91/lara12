<?php

namespace Tests\Unit\Http\Queries;

use App\Http\Queries\BaseQuery;
use App\Http\Queries\Criteria\Criteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Mockery;
use Tests\Unit\UnitTestCase;

class QueryUserStub
{
    public function __construct(public int $id, public string $name)
    {
    }
}

class QueryUserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
        ];
    }
}

class BaseQueryTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_per_page_and_page_bounds_are_applied(): void
    {
        $builder = Mockery::mock(Builder::class);
        $query = $this->makeQuery(new Request(['per_page' => 999, 'page' => 0]), $builder);

        $this->assertSame(100, $query->publicPerPage());
        $this->assertSame(1, $query->publicPage());

        $query2 = $this->makeQuery(new Request(['per_page' => -5, 'page' => -10]), $builder);
        $this->assertSame(1, $query2->publicPerPage());
        $this->assertSame(1, $query2->publicPage());
    }

    public function test_criteria_pipeline_applies_in_order(): void
    {
        $request = new Request(['foo' => 'bar']);
        $builder1 = Mockery::mock(Builder::class);
        $builder2 = Mockery::mock(Builder::class);
        $builder3 = Mockery::mock(Builder::class);
        $query = $this->makeQuery($request, $builder1);

        $criteriaA = Mockery::mock(Criteria::class);
        $criteriaB = Mockery::mock(Criteria::class);

        $criteriaA->shouldReceive('apply')->once()->with($builder1, $request)->andReturn($builder2);
        $criteriaB->shouldReceive('apply')->once()->with($builder2, $request)->andReturn($builder3);

        $query->pushCriteria($criteriaA)->pushCriteria($criteriaB);

        $this->assertSame($builder3, $query->publicAppliedBuilder());
    }

    public function test_get_and_first_without_resource_return_raw_values(): void
    {
        $request = new Request();
        $builder = Mockery::mock(Builder::class);
        $query = $this->makeQuery($request, $builder);

        $collection = collect([new QueryUserStub(1, 'Alice')]);
        $builder->shouldReceive('get')->once()->andReturn($collection);
        $builder->shouldReceive('first')->once()->andReturn(new QueryUserStub(2, 'Bob'));

        $this->assertSame($collection, $query->get());
        $this->assertInstanceOf(QueryUserStub::class, $query->first());
    }

    public function test_get_and_first_with_resource_return_transformed_values(): void
    {
        $request = new Request();
        $builder = Mockery::mock(Builder::class);
        $query = $this->makeQuery($request, $builder);
        $query->setResource(QueryUserResource::class);

        $models = collect([new QueryUserStub(1, 'Alice')]);
        $builder->shouldReceive('get')->once()->andReturn($models);
        $builder->shouldReceive('first')->once()->andReturn(new QueryUserStub(2, 'Bob'));

        $resourceCollection = $query->get();
        $resourceItem = $query->first();

        $this->assertNotNull($resourceCollection);
        $this->assertInstanceOf(QueryUserResource::class, $resourceItem);
    }

    public function test_find_count_exists_and_pluck_delegate_to_builder(): void
    {
        $request = new Request();
        $builder = Mockery::mock(Builder::class);
        $query = $this->makeQuery($request, $builder);

        $builder->shouldReceive('find')->once()->with(10)->andReturn(new QueryUserStub(10, 'Ten'));
        $builder->shouldReceive('count')->once()->andReturn(3);
        $builder->shouldReceive('exists')->once()->andReturn(true);
        $builder->shouldReceive('pluck')->once()->with('name', null)->andReturn(collect(['Alice', 'Bob']));

        $found = $query->findById(10);
        $this->assertInstanceOf(QueryUserStub::class, $found);
        $this->assertSame(3, $query->count());
        $this->assertTrue($query->exists());
        $this->assertCount(2, $query->pluck('name'));
    }

    public function test_find_by_id_with_resource_returns_transformed_value(): void
    {
        $request = new Request();
        $builder = Mockery::mock(Builder::class);
        $query = $this->makeQuery($request, $builder);
        $query->setResource(QueryUserResource::class);

        $builder->shouldReceive('find')->once()->with(10)->andReturn(new QueryUserStub(10, 'Ten'));

        $result = $query->findById(10);

        $this->assertInstanceOf(QueryUserResource::class, $result);
    }

    public function test_all_and_select_items_delegate_to_builder(): void
    {
        $request = new Request();
        $builder = Mockery::mock(Builder::class);
        $query = $this->makeQuery($request, $builder);
        $collection = collect([new QueryUserStub(1, 'Alice')]);
        $selected = collect([['id' => 1]]);

        $builder->shouldReceive('get')->once()->andReturn($collection);
        $builder->shouldReceive('select')->once()->with(['id'])->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn($selected);

        $this->assertSame($collection, $query->all());
        $this->assertSame($selected, $query->selectItems(['id']));
    }

    public function test_paginate_and_resource_paginated_work_without_db(): void
    {
        $request = new Request(['per_page' => 10, 'page' => 2]);
        $builder = Mockery::mock(Builder::class);
        $query = $this->makeQuery($request, $builder);

        $paginator = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
        $builder->shouldReceive('paginate')
            ->once()
            ->with(10, ['*'], 'page', 2)
            ->andReturn($paginator);

        $this->assertSame($paginator, $query->paginate());

        $query->setResource(QueryUserResource::class);
        $paginatorWithResource = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
        $builder->shouldReceive('paginate')
            ->once()
            ->with(10, ['*'], 'page', 2)
            ->andReturn($paginatorWithResource);
        $paginatorWithResource->shouldReceive('through')->once()->andReturnSelf();

        $this->assertSame($paginatorWithResource, $query->paginate());
    }

    public function test_to_resource_helpers_use_builder_results(): void
    {
        $request = new Request();
        $builder = Mockery::mock(Builder::class);
        $query = $this->makeQuery($request, $builder);

        $builder->shouldReceive('get')->once()->andReturn(collect([new QueryUserStub(1, 'Alice')]));
        $builder->shouldReceive('first')->once()->andReturn(new QueryUserStub(1, 'Alice'));
        $paginator = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
        $builder->shouldReceive('paginate')->once()->andReturn($paginator);
        $paginator->shouldReceive('through')->twice()->andReturnSelf();

        $query->setResource(QueryUserResource::class);

        $this->assertNotNull($query->toResource());
        $this->assertInstanceOf(QueryUserResource::class, $query->toResourceSingle());
        $this->assertNotNull($query->toResourcePaginated());
    }

    public function test_to_resource_helpers_without_resource_return_raw_values(): void
    {
        $request = new Request();
        $builder = Mockery::mock(Builder::class);
        $query = $this->makeQuery($request, $builder);
        $collection = collect([new QueryUserStub(1, 'Alice')]);
        $model = new QueryUserStub(2, 'Bob');
        $paginator = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

        $builder->shouldReceive('get')->once()->andReturn($collection);
        $builder->shouldReceive('first')->once()->andReturn($model);
        $builder->shouldReceive('paginate')->once()->andReturn($paginator);

        $this->assertSame($collection, $query->toResource());
        $this->assertSame($model, $query->toResourceSingle());
        $this->assertSame($paginator, $query->toResourcePaginated());
    }

    public function test_boot_adds_default_criteria_when_overridden(): void
    {
        $request = new Request();
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

            protected function filters(): array
            {
                return ['name'];
            }

            protected function sorts(): array
            {
                return ['name'];
            }

            protected function includes(): array
            {
                return ['hotel'];
            }

            public function publicCriteriaCount(): int
            {
                return count($this->criteria);
            }
        };

        $this->assertSame(1, $query->publicCriteriaCount());
    }

    private function makeQuery(Request $request, Builder $builder)
    {
        return new class($request, $builder) extends BaseQuery {
            public function __construct(Request $request, private Builder $builder)
            {
                parent::__construct($request);
            }

            protected function newQuery(): Builder
            {
                return $this->builder;
            }

            public function publicPerPage(): int
            {
                return $this->perPage();
            }

            public function publicPage(): int
            {
                return $this->page();
            }

            public function publicAppliedBuilder(): Builder
            {
                return $this->applyCriteria();
            }
        };
    }
}
