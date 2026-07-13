<?php

namespace Modules\Chain\Tests\Unit\Http\Requests;

use \Modules\Chain\Http\Requests\HotelSearchRequest;
use Tests\Unit\Concerns\AssertsSearchRequestPagination;
use Tests\Unit\UnitTestCase;

class HotelSearchRequestDefaultPerPageBehaviorTest extends UnitTestCase
{
    use AssertsSearchRequestPagination;

    public function test_search_request_default_max_per_page_matches_spec(): void
    {
        $request = new \Modules\Chain\Http\Requests\HotelSearchRequest();

        $this->assertSearchRequestMaxPerPage($request, 100);
    }
}
