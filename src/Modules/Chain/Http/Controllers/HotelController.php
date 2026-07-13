<?php

namespace Modules\Chain\Http\Controllers;

use App\Http\Controllers\Traits\EntrySearchTrait;
use Modules\Chain\Http\Requests\HotelSearchRequest;
use Modules\Chain\Http\Controllers\ChainController;
use Modules\Chain\Http\Actions\HotelAction;
use Modules\Chain\Http\Queries\HotelQuery;

class HotelController extends ChainController
{
    use EntrySearchTrait;


    public function __construct(private readonly HotelAction $action, private readonly HotelQuery $query)
    {

    }


}
