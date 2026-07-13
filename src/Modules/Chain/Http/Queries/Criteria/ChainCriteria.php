<?php

namespace Modules\Chain\Http\Queries\Criteria;

use App\Http\Queries\Criteria\BaseCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class ChainCriteria extends BaseCriteria
{
    public function apply(Builder $query, Request $request): Builder
    {
        // Override in child criteria classes.
        return $query;
    }
}
