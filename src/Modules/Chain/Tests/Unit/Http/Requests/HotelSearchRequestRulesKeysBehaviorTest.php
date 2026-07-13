<?php

namespace Modules\Chain\Tests\Unit\Http\Requests;

use \Modules\Chain\Http\Requests\HotelSearchRequest;
use Tests\Unit\Concerns\AssertsRequestRuleKeys;
use Tests\Unit\UnitTestCase;

class HotelSearchRequestRulesKeysBehaviorTest extends UnitTestCase
{
    use AssertsRequestRuleKeys;

    public function test_search_request_rules_contain_spec_keys(): void
    {
        $request = new \Modules\Chain\Http\Requests\HotelSearchRequest();

        $this->assertRequestRulesContainKeys($request, ['page', 'per_page', 'order_by', 'sorted_by']);
    }
}
