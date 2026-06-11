<?php

namespace Tests\Unit\Http\Queries\Criteria;

use App\Http\Queries\Criteria\BaseCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Mockery;
use Tests\Unit\UnitTestCase;

class BaseCriteriaTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_apply_filters_like_operator(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->once()->with('name', 'like', '%John%')->andReturnSelf();

        $criteria = new BaseCriteria(['name']);
        $result = $criteria->apply($builder, new Request(['filter' => ['name' => 'John']]));

        $this->assertSame($builder, $result);
    }

    public function test_apply_filters_equals_operator(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->once()->with('status', '=', 1)->andReturnSelf();

        $result = (new BaseCriteria([['status', '=']]))
            ->apply($builder, new Request(['filter' => ['status' => 1]]));

        $this->assertSame($builder, $result);
    }

    public function test_apply_filters_in_operator(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('whereIn')->once()->with('status', [1, 2])->andReturnSelf();

        $result = (new BaseCriteria([['status', 'in']]))
            ->apply($builder, new Request(['filter' => ['status' => [1, 2]]]));

        $this->assertSame($builder, $result);
    }

    public function test_apply_filters_column_alias_config(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->once()->with('v_name', 'like', '%Jane%')->andReturnSelf();

        $result = (new BaseCriteria(['q' => ['column' => 'v_name', 'operator' => 'like']]))
            ->apply($builder, new Request(['filter' => ['q' => 'Jane']]));

        $this->assertSame($builder, $result);
    }

    public function test_apply_filters_skips_disallowed_empty_and_invalid_filter_payload(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->never();
        $builder->shouldReceive('whereIn')->never();

        $criteria = new BaseCriteria(['name']);
        $this->assertSame($builder, $criteria->apply($builder, new Request(['filter' => ['password' => 'secret']])));
        $this->assertSame($builder, $criteria->apply($builder, new Request(['filter' => ['name' => '']])));
        $this->assertSame($builder, $criteria->apply($builder, new Request(['filter' => ['name' => null]])));
        $this->assertSame($builder, $criteria->apply($builder, new Request([])));
        $this->assertSame($builder, $criteria->apply($builder, new Request(['filter' => 'bad-string'])));
    }

    public function test_apply_filters_decodes_json_string_filter_payload(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->once()->with('name', 'like', '%John%')->andReturnSelf();

        $criteria = new BaseCriteria(['name']);
        $result = $criteria->apply(
            $builder,
            new Request(['filter' => '{"name":"John","status":1}'])
        );

        $this->assertSame($builder, $result);
    }

    public function test_apply_filters_callable_handler(): void
    {
        $builder = Mockery::mock(Builder::class);
        $called = false;

        $handler = function (Builder $query, mixed $value) use (&$called, $builder): void {
            $called = true;
            $this->assertSame($builder, $query);
            $this->assertSame(18, $value);
            $query->where('age', '=', (int) $value);
        };

        $builder->shouldReceive('where')->once()->with('age', '=', 18)->andReturnSelf();

        (new BaseCriteria(['age' => $handler]))
            ->apply($builder, new Request(['filter' => ['age' => 18]]));

        $this->assertTrue($called);
    }

    public function test_apply_sort_asc_desc_and_legacy_order_by(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('orderBy')->once()->with('name', 'asc')->andReturnSelf();
        $this->assertSame($builder, (new BaseCriteria([], ['name']))->apply($builder, new Request(['sort' => 'name'])));

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('orderBy')->once()->with('name', 'desc')->andReturnSelf();
        $this->assertSame($builder, (new BaseCriteria([], ['name']))->apply($builder, new Request(['sort' => '-name'])));

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('orderBy')->once()->with('name', 'desc')->andReturnSelf();
        $this->assertSame($builder, (new BaseCriteria([], ['name']))->apply($builder, new Request(['order_by' => 'name', 'sorted_by' => 'desc'])));
    }

    public function test_apply_sort_respects_whitelist_and_skips_empty_sort(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('orderBy')->never();
        $this->assertSame($builder, (new BaseCriteria([], ['name']))->apply($builder, new Request(['sort' => 'password'])));

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('orderBy')->never();
        $this->assertSame($builder, (new BaseCriteria([], ['name']))->apply($builder, new Request(['sort' => ''])));
    }

    public function test_apply_includes_whitelist(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('with')->once()->with(['hotel'])->andReturnSelf();

        $result = (new BaseCriteria([], [], ['hotel', 'chain']))
            ->apply($builder, new Request(['include' => 'hotel,secret']));

        $this->assertSame($builder, $result);
    }

    public function test_apply_includes_skips_when_not_allowed_or_empty(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('with')->never();

        $this->assertSame(
            $builder,
            (new BaseCriteria([], [], ['hotel']))->apply($builder, new Request(['include' => 'secret']))
        );

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('with')->never();
        $this->assertSame($builder, (new BaseCriteria([], [], []))->apply($builder, new Request(['include' => 'hotel'])));
    }

    public function test_helper_methods_via_subclass(): void
    {
        $criteria = new class extends BaseCriteria {
            public function publicInput(Request $request, string $key, mixed $default = null): mixed
            {
                return $this->input($request, $key, $default);
            }

            public function publicText(Request $request, string $key): string
            {
                return $this->text($request, $key);
            }

            public function publicHasValue(mixed $value): bool
            {
                return $this->hasValue($value);
            }

            public function publicHasSort(Request $request): bool
            {
                return $this->hasSort($request);
            }
        };

        $request = Request::create('/test', 'GET', ['name' => '  John  ']);
        $this->assertSame('John', $criteria->publicText($request, 'name'));
        $this->assertSame('default@example.com', $criteria->publicInput($request, 'email', 'default@example.com'));
        $this->assertTrue($criteria->publicHasValue(0));
        $this->assertFalse($criteria->publicHasValue(''));
        $this->assertTrue($criteria->publicHasSort(Request::create('/test', 'GET', ['sort' => 'id'])));
    }

    public function test_normalize_allowed_filters_supports_multiple_syntaxes(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->times(3)->andReturnSelf();

        $result = (new BaseCriteria([
            'name' => true,
            'q' => 'email',
            'status' => ['op' => '=', 'column' => 'status'],
        ]))->apply($builder, new Request([
            'filter' => [
                'name' => 'John',
                'q' => 'jane@example.com',
                'status' => 1,
            ],
        ]));

        $this->assertSame($builder, $result);
    }
}
