<?php

namespace Tests\Unit\Http\Queries\Criteria;

use Tests\TestCase;
use App\Http\Queries\Criteria\IncludeCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * IncludeCriteria — kiểm tra eager load được đăng ký đúng.
 *
 * Dùng Builder thật (User::query()).
 * getEagerLoads() trả về map [relation => Closure] mà không cần execute.
 */
class IncludeCriteriaTest extends TestCase
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

    public function test_allowed_includes_are_registered(): void
    {
        $result = (new IncludeCriteria(['hotel', 'chain']))
            ->apply($this->builder(), new Request(['include' => 'hotel,chain']));

        $keys = array_keys($result->getEagerLoads());
        $this->assertContains('hotel', $keys);
        $this->assertContains('chain', $keys);
        $this->assertCount(2, $keys);
    }

    public function test_only_allowed_subset_registered(): void
    {
        $result = (new IncludeCriteria(['hotel', 'chain']))
            ->apply($this->builder(), new Request(['include' => 'hotel,secret']));

        $keys = array_keys($result->getEagerLoads());
        $this->assertContains('hotel', $keys);
        $this->assertNotContains('secret', $keys);
        $this->assertCount(1, $keys);
    }

    public function test_single_allowed_relation(): void
    {
        $result = (new IncludeCriteria(['hotel', 'chain']))
            ->apply($this->builder(), new Request(['include' => 'hotel']));

        $keys = array_keys($result->getEagerLoads());
        $this->assertSame(['hotel'], $keys);
    }

    // ------------------------------------------------------------------
    // Edge / empty — no eager loads
    // ------------------------------------------------------------------

    public function test_empty_include_param_registers_nothing(): void
    {
        $result = (new IncludeCriteria(['hotel']))
            ->apply($this->builder(), new Request(['include' => '']));

        $this->assertEmpty($result->getEagerLoads());
    }

    public function test_no_include_param_registers_nothing(): void
    {
        $result = (new IncludeCriteria(['hotel']))
            ->apply($this->builder(), new Request([]));

        $this->assertEmpty($result->getEagerLoads());
    }

    public function test_all_disallowed_registers_nothing(): void
    {
        $result = (new IncludeCriteria(['hotel']))
            ->apply($this->builder(), new Request(['include' => 'secret,another']));

        $this->assertEmpty($result->getEagerLoads());
    }

    public function test_empty_allowed_list_rejects_all(): void
    {
        $result = (new IncludeCriteria([]))
            ->apply($this->builder(), new Request(['include' => 'hotel']));

        $this->assertEmpty($result->getEagerLoads());
    }
}
