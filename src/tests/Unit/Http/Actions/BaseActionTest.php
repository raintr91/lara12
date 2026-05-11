<?php

namespace Tests\Unit\Http\Actions;

use Tests\TestCase;
use App\Http\Actions\BaseAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DummyActionModel extends Model
{
    protected $guarded = [];
    public $timestamps = false;
    public bool $wasDeleted = false;

    public static function create(array $attributes = []): static
    {
        $model = new static();
        $model->exists = false;
        $model->forceFill($attributes);

        return $model;
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

class BaseActionTest extends TestCase
{
    public function test_execute_delegates_to_run(): void
    {
        $action = new class extends BaseAction {
            protected function run(...$args)
            {
                return ['args' => $args];
            }
        };

        $result = $action->execute(['name' => 'John']);

        $this->assertSame(['args' => [['name' => 'John']]], $result);
    }

    public function test_transaction_returns_callback_result(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $action = new class extends BaseAction {
            protected function run(...$args)
            {
                return null;
            }

            public function runTransaction(callable $callback)
            {
                return $this->transaction($callback);
            }
        };

        $this->assertSame('ok', $action->runTransaction(fn () => 'ok'));
    }

    public function test_create_update_and_delete_helpers(): void
    {
        $action = new class extends BaseAction {
            protected function run(...$args)
            {
                return null;
            }

            public function callCreate(string $modelClass, array $attributes): Model
            {
                return $this->create($modelClass, $attributes);
            }

            public function callUpdate(Model $model, array $attributes): Model
            {
                return $this->update($model, $attributes);
            }

            public function callDelete(Model $model): void
            {
                $this->delete($model);
            }
        };

        $created = $action->callCreate(DummyActionModel::class, ['name' => 'Before']);
        $this->assertSame('Before', $created->name);
        $this->assertFalse($created->wasDeleted);

        $updated = $action->callUpdate($created, ['name' => 'After']);
        $this->assertSame('After', $updated->name);

        $action->callDelete($updated);
        $this->assertTrue($updated->wasDeleted);
    }

    public function test_sync_relations_covers_all_relation_types_and_missing_relation(): void
    {
        $action = new class extends BaseAction {
            protected function run(...$args)
            {
                return null;
            }

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
