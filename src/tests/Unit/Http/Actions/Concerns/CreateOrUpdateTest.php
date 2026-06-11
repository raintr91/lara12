<?php

namespace Tests\Unit\Http\Actions\Concerns;

use Tests\Unit\UnitTestCase;
use App\Http\Actions\Concerns\CreateOrUpdate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;

class CreateOrUpdateTest extends UnitTestCase
{
    protected object $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new class {
            use CreateOrUpdate;

            public int $transactions = 0;
            public $query;

            protected function transaction(callable $callback)
            {
                $this->transactions++;
                return $callback();
            }

            protected function newQuery()
            {
                return $this->query;
            }

            public function publicCreateOrUpdateBy(array $conditions, array $data): Model
            {
                return $this->createOrUpdateBy($conditions, $data);
            }

            public function publicFindOrFail(int|string $id): Model
            {
                return $this->findOrFail($id);
            }

            public function publicUpdateById(int|string $id, array $data): Model
            {
                return $this->updateById($id, $data);
            }

            public function publicDeleteById(int|string $id): bool
            {
                return $this->deleteById($id);
            }
        };
    }

    /**
     * Test findOrFail method.
     */
    public function test_find_or_fail_throws_when_missing(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $query = Mockery::mock();
        $query->shouldReceive('findOrFail')->with(999)->andThrow(new ModelNotFoundException());
        $this->action->query = $query;

        $this->action->publicFindOrFail(999);
    }

    /**
     * Test delete method removes model.
     */
    public function test_delete_by_id(): void
    {
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('delete')->once()->andReturn(true);

        $query = Mockery::mock();
        $query->shouldReceive('findOrFail')->with(10)->andReturn($model);
        $this->action->query = $query;

        $result = $this->action->publicDeleteById(10);

        $this->assertTrue($result);
        $this->assertSame(1, $this->action->transactions);
    }

    /**
     * Test create method instantiates model.
     */
    public function test_new_query_uses_model_class(): void
    {
        $builder = Mockery::mock();
        CreateOrUpdateFakeModel::$fakeBuilder = $builder;

        $action = new class {
            use CreateOrUpdate;

            public Model $model;

            public function __construct()
            {
                $this->model = new CreateOrUpdateFakeModel();
            }

            public function callNewQuery()
            {
                return $this->newQuery();
            }
        };

        $this->assertSame($builder, $action->callNewQuery());
    }

    public function test_create_or_update_by(): void
    {
        $model = Mockery::mock(Model::class);

        $query = Mockery::mock();
        $query->shouldReceive('updateOrCreate')
            ->with(['id' => 1], ['name' => 'John'])
            ->andReturn($model);
        $this->action->query = $query;

        $result = $this->action->publicCreateOrUpdateBy(['id' => 1], ['name' => 'John']);

        $this->assertSame($model, $result);
        $this->assertSame(1, $this->action->transactions);
    }

    /**
     * Test update method updates model.
     */
    public function test_update_by_id(): void
    {
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('update')->with(['name' => 'Jane'])->once()->andReturn(true);
        $model->shouldReceive('refresh')->once()->andReturn($model);

        $query = Mockery::mock();
        $query->shouldReceive('findOrFail')->with(10)->andReturn($model);
        $this->action->query = $query;

        $result = $this->action->publicUpdateById(10, ['name' => 'Jane']);

        $this->assertSame($model, $result);
        $this->assertSame(1, $this->action->transactions);
    }

    // ------------------------------------------------------------------
    // Exception boundary — mock tại biên PDO / QueryException
    // DB thật có thể throw các lỗi sau; test phải đảm bảo chúng propagate
    // ------------------------------------------------------------------

    public function test_find_or_fail_propagates_pdo_exception(): void
    {
        // Biên: DB server không connect được
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage('Connection refused');

        $query = Mockery::mock();
        $query->shouldReceive('findOrFail')
            ->andThrow(new \PDOException('Connection refused'));
        $this->action->query = $query;

        $this->action->publicFindOrFail(1);
    }

    public function test_find_or_fail_propagates_query_exception(): void
    {
        // Biên: câu query lỗi (lỗi SQL, table không tồn tại, ...)
        $this->expectException(\Illuminate\Database\QueryException::class);

        $query = Mockery::mock();
        $query->shouldReceive('findOrFail')
            ->andThrow(new \Illuminate\Database\QueryException(
                'mysql',
                'select * from `missing_table` where id = ?',
                [1],
                new \PDOException("Table 'missing_table' doesn't exist")
            ));
        $this->action->query = $query;

        $this->action->publicFindOrFail(1);
    }

    public function test_update_propagates_query_exception_on_deadlock(): void
    {
        // Biên: deadlock khi update
        $this->expectException(\Illuminate\Database\QueryException::class);

        $model = Mockery::mock(Model::class);
        $model->shouldReceive('update')
            ->andThrow(new \Illuminate\Database\QueryException(
                'mysql',
                'update `users` set `name` = ?',
                ['Jane'],
                new \PDOException('Deadlock found when trying to get lock')
            ));

        $query = Mockery::mock();
        $query->shouldReceive('findOrFail')->andReturn($model);
        $this->action->query = $query;

        $this->action->publicUpdateById(10, ['name' => 'Jane']);
    }

    public function test_create_or_update_propagates_connection_lost(): void
    {
        // Biên: mất kết nối DB giữa chừng
        $this->expectException(\PDOException::class);

        $query = Mockery::mock();
        $query->shouldReceive('updateOrCreate')
            ->andThrow(new \PDOException('MySQL server has gone away'));
        $this->action->query = $query;

        $this->action->publicCreateOrUpdateBy(['id' => 1], ['name' => 'x']);
    }

    public function test_delete_propagates_foreign_key_violation(): void
    {
        // Biên: xóa bị chặn bởi FK constraint
        $this->expectException(\Illuminate\Database\QueryException::class);

        $model = Mockery::mock(Model::class);
        $model->shouldReceive('delete')
            ->andThrow(new \Illuminate\Database\QueryException(
                'mysql',
                'delete from `users` where id = ?',
                [10],
                new \PDOException('Cannot delete or update a parent row: a foreign key constraint fails')
            ));

        $query = Mockery::mock();
        $query->shouldReceive('findOrFail')->andReturn($model);
        $this->action->query = $query;

        $this->action->publicDeleteById(10);
    }
}

class CreateOrUpdateFakeModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public static ?object $fakeBuilder = null;

    public static function query()
    {
        return static::$fakeBuilder;
    }
}
