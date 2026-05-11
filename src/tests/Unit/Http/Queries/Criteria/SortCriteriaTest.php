<?php

namespace Tests\Unit\Http\Queries\Criteria;

use Tests\TestCase;
use App\Http\Queries\Criteria\SortCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * SortCriteria — assert ORDER BY SQL được build đúng.
 *
 * Dùng Builder thật (User::query()), chỉ gọi toSql() — không execute.
 */
class SortCriteriaTest extends TestCase
{
    private function builder(): Builder
    {
        return (new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'users';
            public $timestamps = false;
        })->newQuery();
    }

    // ------------------------------------------------------------------
    // Normal flow
    // ------------------------------------------------------------------

    public function test_sort_ascending_adds_order_by_asc(): void
    {
        $result = (new SortCriteria(['name']))
            ->apply($this->builder(), new Request(['sort' => 'name']));

        $sql = strtolower($result->toSql());
        $this->assertStringContainsString('order by', $sql);
        $this->assertStringContainsString('asc', $sql);
    }

    public function test_sort_descending_minus_prefix(): void
    {
        $result = (new SortCriteria(['name']))
            ->apply($this->builder(), new Request(['sort' => '-name']));

        $sql = strtolower($result->toSql());
        $this->assertStringContainsString('order by', $sql);
        $this->assertStringContainsString('desc', $sql);
    }

    public function test_legacy_order_by_param_maps_to_asc(): void
    {
        $result = (new SortCriteria(['name']))
            ->apply($this->builder(), new Request(['order_by' => 'name']));

        $sql = strtolower($result->toSql());
        $this->assertStringContainsString('order by', $sql);
        $this->assertStringContainsString('asc', $sql);
    }

    public function test_legacy_order_by_with_sorted_by_desc(): void
    {
        $result = (new SortCriteria(['name']))
            ->apply($this->builder(), new Request(['order_by' => 'name', 'sorted_by' => 'desc']));

        $sql = strtolower($result->toSql());
        $this->assertStringContainsString('order by', $sql);
        $this->assertStringContainsString('desc', $sql);
    }

    public function test_empty_whitelist_allows_any_field(): void
    {
        $result = (new SortCriteria([]))
            ->apply($this->builder(), new Request(['sort' => 'anything']));

        $sql = strtolower($result->toSql());
        $this->assertStringContainsString('order by', $sql);
    }

    // ------------------------------------------------------------------
    // Edge / boundary — no ORDER BY added
    // ------------------------------------------------------------------

    public function test_disallowed_field_produces_no_order_by(): void
    {
        $result = (new SortCriteria(['name']))
            ->apply($this->builder(), new Request(['sort' => 'password']));

        $this->assertStringNotContainsString('order by', strtolower($result->toSql()));
    }

    public function test_no_sort_param_produces_no_order_by(): void
    {
        $result = (new SortCriteria(['name']))
            ->apply($this->builder(), new Request([]));

        $this->assertStringNotContainsString('order by', strtolower($result->toSql()));
    }

    public function test_empty_sort_string_produces_no_order_by(): void
    {
        $result = (new SortCriteria(['name']))
            ->apply($this->builder(), new Request(['sort' => '']));

        $this->assertStringNotContainsString('order by', strtolower($result->toSql()));
    }

    public function test_sort_takes_priority_over_legacy_order_by(): void
    {
        // Khi có cả sort lẫn order_by, sort phải thắng
        $result = (new SortCriteria(['name', 'email']))
            ->apply($this->builder(), new Request(['sort' => 'email', 'order_by' => 'name']));

        $sql = strtolower($result->toSql());
        // Chỉ 1 order by clause
        $this->assertSame(1, substr_count($sql, 'order by'));
        $this->assertStringContainsString('asc', $sql);
    }
}
