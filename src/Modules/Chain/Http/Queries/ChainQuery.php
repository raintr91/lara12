<?php

namespace Modules\Chain\Http\Queries;

use App\Http\Queries\BaseQuery;
use Illuminate\Database\Eloquent\Builder;

abstract class ChainQuery extends BaseQuery
{
    // Override `newQuery(): Builder` in child query classes.
}
