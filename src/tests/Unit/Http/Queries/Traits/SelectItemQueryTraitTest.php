<?php

namespace Tests\Unit\Http\Queries\Traits;

use App\Http\Queries\Traits\SelectItemQueryTrait;
use App\Http\Requests\SelectItemRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery;
use RuntimeException;
use Tests\Unit\UnitTestCase;

class SelectItemQueryTraitTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_list_select_items_requires_select_item_request(): void
    {
        $query = new class(new \Illuminate\Http\Request()) {
            use SelectItemQueryTrait;

            public function __construct(public $request)
            {
            }

            public function applyCriteria()
            {
                return Mockery::mock();
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('request must extend');

        $query->getListSelectItems();
    }

    public function test_get_list_select_items_accepts_passed_request_when_constructor_has_generic_request(): void
    {
        $selectRequest = new class extends SelectItemRequest {
            protected string $model = SelectItemQueryDummyModel::class;
        };
        $selectRequest->replace([
            'key' => 'id',
            'name' => 'name',
            'info' => [],
        ]);

        $builder = Mockery::mock();
        $builder->shouldReceive('select')->once()->with(['id', 'name'])->andReturnSelf();
        $builder->shouldReceive('orderBy')->once()->with('name')->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn(collect([['id' => 3, 'name' => 'C']]));

        $query = new class(new \Illuminate\Http\Request(), $builder) {
            use SelectItemQueryTrait;

            public Model $model;

            public function __construct(public $request, private $builder)
            {
                $this->model = new SelectItemQueryDummyModel();
            }

            public function applyCriteria()
            {
                return $this->builder;
            }
        };

        $items = $query->getListSelectItems($selectRequest);

        $this->assertSame([['id' => 3, 'name' => 'C']], $items->toArray());
    }

    public function test_get_list_select_items_supports_string_name_field(): void
    {
        $request = new class extends SelectItemRequest {
            protected string $model = SelectItemQueryDummyModel::class;
        };
        $request->replace([
            'key' => 'id',
            'name' => 'name',
            'info' => [],
        ]);

        $builder = Mockery::mock();
        $builder->shouldReceive('select')->once()->with(['id', 'name'])->andReturnSelf();
        $builder->shouldReceive('orderBy')->once()->with('name')->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn(collect([['id' => 7, 'name' => 'String Name']]));

        $query = new class($request, $builder) {
            use SelectItemQueryTrait;

            public Model $model;

            public function __construct(public $request, private $builder)
            {
                $this->model = new SelectItemQueryDummyModel();
            }

            public function applyCriteria()
            {
                return $this->builder;
            }
        };

        $items = $query->getListSelectItems();

        $this->assertSame([['id' => 7, 'name' => 'String Name']], $items->toArray());
    }

    public function test_get_list_select_items_returns_collection_with_relations(): void
    {
        $request = new class extends SelectItemRequest {
            protected string $model = SelectItemQueryDummyModel::class;
        };
        $request->replace([
            'key' => 'id',
            'name' => ['name'],
            'info' => ['status', 'related', 'related.name', 'broken.name', 'ignored_field'],
        ]);

        $builder = Mockery::mock();
        $builder->shouldReceive('select')->once()->withArgs(function (array $columns) {
            return in_array('id', $columns, true)
                && in_array('name', $columns, true)
                && in_array('status', $columns, true)
                && in_array('related_id', $columns, true);
        })->andReturnSelf();
        $builder->shouldReceive('with')->once()->withArgs(function (array $with) {
            if (!array_key_exists('related', $with) || !is_callable($with['related'])) {
                return false;
            }

            $relationQuery = Mockery::mock();
            $relationQuery->shouldReceive('select')->once();
            $with['related']($relationQuery);

            return true;
        })->andReturnSelf();
        $builder->shouldReceive('orderBy')->once()->with('name')->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn(collect([['id' => 1, 'name' => 'A']]));

        $query = new class($request, $builder) {
            use SelectItemQueryTrait;

            public Model $model;

            public function __construct(public $request, private $builder)
            {
                $this->model = new SelectItemQueryDummyModel();
            }

            public function applyCriteria()
            {
                return $this->builder;
            }
        };

        $items = $query->getListSelectItems();

        $this->assertInstanceOf(Collection::class, $items);
        $this->assertSame([['id' => 1, 'name' => 'A']], $items->toArray());
    }

    public function test_get_list_select_items_continues_when_relation_disappears(): void
    {
        $request = new class extends SelectItemRequest {
            protected string $model = SelectItemQueryFlakyRelationModel::class;
        };
        $request->replace([
            'key' => 'id',
            'name' => ['name'],
            'info' => ['flaky'],
        ]);

        $builder = Mockery::mock();
        $builder->shouldReceive('select')->once()->andReturnSelf();
        $builder->shouldReceive('with')->never();
        $builder->shouldReceive('orderBy')->once()->with('name')->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn(collect([['id' => 9, 'name' => 'X']]));

        $query = new class($request, $builder) {
            use SelectItemQueryTrait;

            public Model $model;

            public function __construct(public $request, private $builder)
            {
                $this->model = new SelectItemQueryFlakyRelationModel();
            }

            public function applyCriteria()
            {
                return $this->builder;
            }
        };

        $items = $query->getListSelectItems();

        $this->assertSame([['id' => 9, 'name' => 'X']], $items->toArray());
    }

    public function test_get_list_select_items_uses_paginate_when_requested(): void
    {
        $request = new class extends SelectItemRequest {
            protected string $model = SelectItemQueryDummyModel::class;
        };
        $request->replace([
            'key' => 'id',
            'name' => ['name'],
            'info' => [],
            'per_page' => 10,
            'page' => 1,
        ]);

        $paginator = new LengthAwarePaginator([
            ['id' => 2, 'name' => 'B'],
        ], 1, 10, 1);

        $builder = Mockery::mock();
        $builder->shouldReceive('select')->once()->andReturnSelf();
        $builder->shouldReceive('orderBy')->once()->with('name')->andReturnSelf();
        $builder->shouldReceive('paginate')->once()->andReturn($paginator);

        $query = new class($request, $builder) {
            use SelectItemQueryTrait;

            public Model $model;

            public function __construct(public $request, private $builder)
            {
                $this->model = new SelectItemQueryDummyModel();
            }

            public function applyCriteria()
            {
                return $this->builder;
            }
        };

        $items = $query->getListSelectItems();

        $this->assertInstanceOf(Collection::class, $items);
        $this->assertSame([['id' => 2, 'name' => 'B']], $items->toArray());
    }

    public function test_private_helpers_parse_relations_and_fields(): void
    {
        $request = new class extends SelectItemRequest {
            protected string $model = SelectItemQueryDummyModel::class;
        };

        $query = new SelectItemQueryTraitInspectable($request);
        $query->model = new SelectItemQueryDummyModel();
        $model = $query->model;

        [$scalars, $relations, $relationFields] = $query->parseInfoPublic(
            ['status', 'related', 'related.name', 'missing.name', 'broken.name', 123, '']
        );

        $this->assertSame(['status'], $scalars);
        $this->assertSame(['related'], $relations);
        $this->assertSame(['related' => ['name']], $relationFields);

        $this->assertTrue($query->isRelationNamePublic('related'));
        $this->assertFalse($query->isRelationNamePublic('unknown'));

        $this->assertNull($query->relationInstancePublic('unknown'));
        $this->assertNull($query->relationInstancePublic('broken'));

        $relation = $model->related();
        $defaultFields = $query->relationSelectFieldsPublic($relation, []);
        $requestedFields = $query->relationSelectFieldsPublic($relation, ['code']);
        $hasManyFields = $query->relationSelectFieldsPublic($model->children(), []);

        $this->assertContains('id', $defaultFields);
        $this->assertContains('name', $defaultFields);
        $this->assertContains('id', $requestedFields);
        $this->assertContains('code', $requestedFields);
        $this->assertContains('dummy_id', $hasManyFields);
    }
}

class SelectItemQueryRelatedModel extends Model
{
    protected $fillable = ['name', 'code'];
}

class SelectItemQueryDummyModel extends Model
{
    protected $fillable = ['name', 'status', 'related_id'];

    public function related()
    {
        return $this->belongsTo(SelectItemQueryRelatedModel::class, 'related_id');
    }

    public function broken()
    {
        throw new RuntimeException('broken relation');
    }

    public function children()
    {
        return $this->hasMany(SelectItemQueryRelatedModel::class, 'dummy_id');
    }
}

class SelectItemQueryFlakyRelationModel extends Model
{
    protected $fillable = ['name', 'flaky_id'];

    private int $relationCalls = 0;

    public function flaky()
    {
        $this->relationCalls++;

        if ($this->relationCalls === 1) {
            return $this->belongsTo(SelectItemQueryRelatedModel::class, 'flaky_id');
        }

        return null;
    }
}

class SelectItemQueryTraitInspectable
{
    use SelectItemQueryTrait {
        parseInfo as public parseInfoPublic;
        isRelationName as public isRelationNamePublic;
        relationInstance as public relationInstancePublic;
        relationSelectFields as public relationSelectFieldsPublic;
    }

    public Model $model;

    public function __construct(public $request)
    {
    }

    public function applyCriteria()
    {
        return Mockery::mock();
    }
}
