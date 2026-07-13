<?php

namespace Modules\Chain\Http\Queries;

use App\Models\Platform\Hotel;
use Illuminate\Database\Eloquent\Builder;

class HotelQuery extends ChainQuery
{
    protected function newQuery(): ?Builder
    {
        return Hotel::query();
    }
}
