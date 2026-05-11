<?php

namespace App\Http\Queries\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

interface Criteria
{
    public function apply(Builder $query, Request $request): Builder;
}
