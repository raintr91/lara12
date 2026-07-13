<?php

namespace Modules\Chain\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

abstract class ChainResource extends BaseResource
{
    protected function fields(Request $request): array
    {
        return [];
    }
}
