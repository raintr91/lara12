<?php

namespace Tests\Unit\Support;

use App\Http\Queries\Criteria\Criteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SampleAutoCriteria implements Criteria
{
    public static bool $applied = false;

    public function apply(Builder $query, Request $request): Builder
    {
        self::$applied = true;

        return $query;
    }
}
