<?php

namespace Modules\Chain\Http\Actions;

use App\Models\Platform\Hotel;

class HotelAction extends ChainAction
{
    public function __construct(Hotel $model)
    {
        $this->model = $model;
    }
}
