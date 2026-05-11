<?php

namespace App\Http\Queries\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class IncludeCriteria implements Criteria
{
    protected array $allowed;

    public function __construct(array $allowed)
    {
        $this->allowed = $allowed;
    }

    public function apply(Builder $query, Request $request): Builder
    {
        $includes = array_intersect(
            explode(',', (string) $request->get('include')),
            $this->allowed
        );

        return empty($includes)
            ? $query
            : $query->with($includes);
    }
}
