<?php

namespace Tests\Unit\Http\Queries\Criteria;

use App\Http\Queries\Criteria\Criteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Tests\Unit\UnitTestCase;

class CriteriaInterfaceTest extends UnitTestCase
{
    public function test_can_be_implemented(): void
    {
        $criteria = new class implements Criteria {
            public function apply(Builder $query, Request $request): Builder
            {
                return $query;
            }
        };

        $this->assertInstanceOf(Criteria::class, $criteria);
    }
}
