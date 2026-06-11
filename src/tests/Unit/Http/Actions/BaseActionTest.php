<?php

namespace Tests\Unit\Http\Actions;

use App\Http\Actions\BaseAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\Unit\UnitTestCase;

class StubActionModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public static ?Builder $queryBuilder = null;

    public bool $wasDeleted = false;

    public static function query(): Builder
    {
        return self::$queryBuilder ?? Mockery::mock(Builder::class);
    }

    public static function create(array $attributes = []): static
    {
        $model = new static();
        $model->exists = true;
        $model->forceFill($attributes);

        return $model;
    }

    public function refresh(): static
    {
        return $this;
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        $this->forceFill($attributes);

        return true;
    }

    public function delete(): ?bool
    {
        $this->wasDeleted = true;

        return true;
    }
}

/** Model with instance create() used by BaseAction::create(). */
class InstanceCreateActionModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function create(array $attributes = []): static
    {
        $model = new static();
        $model->exists = true;
        $model->forceFill($attributes);

        return $model;
    }
}

class FakeSyncRelation
{
    public array $last = [];

    public function sync($value): void
    {
        $this->last = $value;
    }
}

class FakeManyRelation
{
    public bool $deleted = false;

    public array $created = [];

    public function delete(): void
    {
        $this->deleted = true;
    }

    public function createMany($value): void
    {
        $this->created = $value;
    }
}

class FakeOneRelation
{
    public array $updated = [];

    public function update($value): void
    {
        $this->updated = $value;
    }
}

class RelationActionModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public FakeSyncRelation $tagsRelation;

    public FakeManyRelation $itemsRelation;

    public FakeOneRelation $profileRelation;

    public function tags()
    {
        return $this->tagsRelation;
    }

    public function items()
    {
        return $this->itemsRelation;
    }

    public function profile()
    {
        return $this->profileRelation;
    }
}

class BaseActionTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        StubActionModel::$queryBuilder = null;
        Mockery::close();
        parent::tearDown();
    }

    public function test_transaction_returns_callback_result(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $action = new class extends BaseAction {
            public function runTransaction(callable $callback): mixed
            {
                return $this->transaction($callback);
            }
        };

        $this->assertSame('ok', $action->runTransaction(fn () => 'ok'));
    }

    public function test_create_model_update_model_and_delete_model_helpers(): void
    {
        $action = new class extends BaseAction {
            public function __construct()
            {
                $this->model = new StubActionModel();
            }

            public function callCreateModel(array $attributes, array $relations = []): Model
            {
                return $this->createModel($attributes, $relations);
            }

            public function callUpdateModel(Model $model, array $attributes, array $relations = []): Model
            {
                return $this->updateModel($model, $attributes, $relations);
            }

            public function callDeleteModel(Model $model): void
            {
                $this->deleteModel($model);
            }
        };

        $created = $action->callCreateModel(['name' => 'Before']);
        $this->assertSame('Before', $created->name);

        $updated = $action->callUpdateModel($created, ['name' => 'After']);
        $this->assertSame('After', $updated->name);

        $action->callDeleteModel($updated);
        $this->assertTrue($updated->wasDeleted);
    }

    public function test_update_delete_and_bulk_delete_use_query_builder(): void
    {
        DB::shouldReceive('transaction')->times(3)->andReturnUsing(fn (callable $cb) => $cb());

        $builder = Mockery::mock(Builder::class);
        $found = StubActionModel::create(['name' => 'Found']);

        $builder->shouldReceive('findOrFail')->once()->with(5)->andReturn($found);
        $builder->shouldReceive('findOrFail')->once()->with(9)->andReturn($found);
        $builder->shouldReceive('whereIn')->once()->with('id', [1, 2])->andReturnSelf();
        $builder->shouldReceive('delete')->once()->andReturn(2);

        StubActionModel::$queryBuilder = $builder;

        $action = new class extends BaseAction {
            public function __construct()
            {
                $this->model = new StubActionModel();
            }
        };

        $this->assertSame('After', $action->update(5, ['name' => 'After'])->name);
        $this->assertNull($action->delete(9));
        $this->assertTrue($action->bulkDelete([1, 2]));
    }

    public function test_create_delegates_to_model_instance(): void
    {
        $action = new class extends BaseAction {
            public function __construct()
            {
                $this->model = new InstanceCreateActionModel();
            }
        };

        $this->assertSame('New', $action->create(['name' => 'New'])->name);
    }

    public function test_update_uses_build_control_payload(): void
    {
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn (callable $cb) => $cb());

        $found = StubActionModel::create(['name' => 'Before']);
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('findOrFail')->once()->with(3)->andReturn($found);
        StubActionModel::$queryBuilder = $builder;

        $action = new class extends BaseAction {
            public function __construct()
            {
                $this->model = new StubActionModel();
            }

            protected function transformPayload(array $data): array
            {
                $data['name'] = strtoupper((string) ($data['name'] ?? ''));

                return $data;
            }
        };

        $this->assertSame('AFTER', $action->update(3, ['name' => 'after'])->name);
    }

    public function test_bulk_delete_returns_false_when_nothing_deleted(): void
    {
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn (callable $cb) => $cb());

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('whereIn')->once()->with('id', [99])->andReturnSelf();
        $builder->shouldReceive('delete')->once()->andReturn(0);
        StubActionModel::$queryBuilder = $builder;

        $action = new class extends BaseAction {
            public function __construct()
            {
                $this->model = new StubActionModel();
            }
        };

        $this->assertFalse($action->bulkDelete([99]));
    }

    public function test_sync_relations_covers_all_relation_types_and_missing_relation(): void
    {
        $action = new class extends BaseAction {
            public function callSync(Model $model, array $relations): void
            {
                $this->syncRelations($model, $relations);
            }
        };

        $model = new RelationActionModel();
        $model->tagsRelation = new FakeSyncRelation();
        $model->itemsRelation = new FakeManyRelation();
        $model->profileRelation = new FakeOneRelation();

        $action->callSync($model, [
            'tags' => [1, 2],
            'items' => [['name' => 'x']],
            'profile' => ['name' => 'p'],
            'missing' => ['ignored' => true],
        ]);

        $this->assertSame([1, 2], $model->tagsRelation->last);
        $this->assertTrue($model->itemsRelation->deleted);
        $this->assertSame([['name' => 'x']], $model->itemsRelation->created);
        $this->assertSame(['name' => 'p'], $model->profileRelation->updated);
    }
}
