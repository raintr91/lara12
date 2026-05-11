<?php

namespace Tests\Unit\Http\Queries\Criteria;

use Tests\TestCase;
use App\Http\Queries\Criteria\FilterCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * FilterCriteria — kiểm tra SQL được build, KHÔNG execute query.
 *
 * Dùng Eloquent Builder thật (User::query()) để assert toSql() + getBindings().
 * PDO connection là lazy — toSql() không cần DB server đang chạy.
 *
 * Mock chỉ dùng ở test exception (callable handler khi DB throw).
 */
class FilterCriteriaTest extends TestCase
{
    /**
     * Builder sạch, không gắn global scope SoftDeletes.
     * Dùng anonymous model để test chỉ assert đúng clause do Criteria thêm.
     */
    private function builder(): Builder
    {
        return (new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'users';
            public $timestamps = false;
        })->newQuery();
    }

    // ------------------------------------------------------------------
    // Normal flow — real Builder, assert SQL + bindings
    // ------------------------------------------------------------------

    public function test_like_operator_builds_wrapped_binding(): void
    {
        $result = (new FilterCriteria(['name']))
            ->apply($this->builder(), new Request(['filter' => ['name' => 'John']]));

        $this->assertStringContainsString('like', $result->toSql());
        $this->assertEquals(['%John%'], $result->getBindings());
    }

    public function test_tuple_syntax_like_produces_same_sql(): void
    {
        $result = (new FilterCriteria([['name', 'like']]))
            ->apply($this->builder(), new Request(['filter' => ['name' => 'John']]));

        $this->assertStringContainsString('like', $result->toSql());
        $this->assertEquals(['%John%'], $result->getBindings());
    }

    public function test_equals_operator_binds_exact_value(): void
    {
        $result = (new FilterCriteria([['status', '=']]))
            ->apply($this->builder(), new Request(['filter' => ['status' => 1]]));

        $this->assertStringContainsString('=', $result->toSql());
        $this->assertEquals([1], $result->getBindings());
    }

    public function test_in_operator_with_array_value(): void
    {
        $result = (new FilterCriteria([['status', 'in']]))
            ->apply($this->builder(), new Request(['filter' => ['status' => [1, 2, 3]]]));

        $this->assertStringContainsString('in', strtolower($result->toSql()));
        $this->assertEquals([1, 2, 3], $result->getBindings());
    }

    public function test_column_alias_in_array_config(): void
    {
        // config: filter field = 'q', map → column 'name', operator 'like'
        $result = (new FilterCriteria(['q' => ['column' => 'name', 'operator' => 'like']]))
            ->apply($this->builder(), new Request(['filter' => ['q' => 'Jane']]));

        $this->assertStringContainsString('like', $result->toSql());
        $this->assertEquals(['%Jane%'], $result->getBindings());
    }

    // ------------------------------------------------------------------
    // Edge / boundary — nothing added to SQL
    // ------------------------------------------------------------------

    public function test_disallowed_field_adds_no_clause(): void
    {
        $result = (new FilterCriteria(['name']))
            ->apply($this->builder(), new Request(['filter' => ['password' => 'secret']]));

        // No WHERE clause
        $this->assertStringNotContainsString('where', strtolower($result->toSql()));
        $this->assertEmpty($result->getBindings());
    }

    public function test_empty_string_value_skipped(): void
    {
        $result = (new FilterCriteria(['name']))
            ->apply($this->builder(), new Request(['filter' => ['name' => '']]));

        $this->assertStringNotContainsString('where', strtolower($result->toSql()));
    }

    public function test_null_value_skipped(): void
    {
        $result = (new FilterCriteria(['name']))
            ->apply($this->builder(), new Request(['filter' => ['name' => null]]));

        $this->assertStringNotContainsString('where', strtolower($result->toSql()));
    }

    public function test_no_filter_param_returns_unmodified_builder(): void
    {
        $result = (new FilterCriteria(['name']))
            ->apply($this->builder(), new Request([]));

        $this->assertStringNotContainsString('where', strtolower($result->toSql()));
    }

    public function test_non_array_filter_param_ignored(): void
    {
        // filter=bad-string (not array) should not crash
        $result = (new FilterCriteria(['name']))
            ->apply($this->builder(), new Request(['filter' => 'bad-string']));

        $this->assertStringNotContainsString('where', strtolower($result->toSql()));
    }

    // ------------------------------------------------------------------
    // Callable handler — invoked with ($builder, $value)
    // ------------------------------------------------------------------

    public function test_callable_handler_receives_builder_and_value(): void
    {
        $captured = [];
        $handler  = function (Builder $q, mixed $val) use (&$captured): void {
            $captured = ['builder' => $q, 'value' => $val];
        };

        (new FilterCriteria(['age' => $handler]))
            ->apply($this->builder(), new Request(['filter' => ['age' => 18]]));

        $this->assertInstanceOf(Builder::class, $captured['builder']);
        $this->assertSame(18, $captured['value']);
    }

    public function test_callable_can_modify_builder(): void
    {
        $handler = fn(Builder $q, mixed $v) => $q->where('status', '=', (int) $v);

        $result = (new FilterCriteria(['status' => $handler]))
            ->apply($this->builder(), new Request(['filter' => ['status' => 1]]));

        $this->assertEquals([1], $result->getBindings());
    }

    // ------------------------------------------------------------------
    // Exception boundary — mock ở đây vì DB là tầng ngoài cùng
    // ------------------------------------------------------------------

    public function test_callable_handler_exception_propagates(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('downstream error');

        $handler = fn() => throw new \RuntimeException('downstream error');

        (new FilterCriteria(['name' => $handler]))
            ->apply($this->builder(), new Request(['filter' => ['name' => 'x']]));
    }

    public function test_normalize_true_and_string_and_array_op_paths(): void
    {
        $result = (new FilterCriteria([
            'name' => true,
            'q' => 'name',
            'status' => ['op' => '=', 'column' => 'status'],
        ]))->apply($this->builder(), new Request([
            'filter' => [
                'name' => 'John',
                'q' => 'Jane',
                'status' => 1,
            ],
        ]));

        $sql = strtolower($result->toSql());
        $this->assertStringContainsString('where', $sql);
        $this->assertCount(3, $result->getBindings());
    }
}
