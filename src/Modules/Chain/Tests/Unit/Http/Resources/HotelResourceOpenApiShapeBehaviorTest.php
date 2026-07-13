<?php

namespace Modules\Chain\Tests\Unit\Http\Resources;

use Illuminate\Http\Request;
use \Modules\Chain\Http\Resources\HotelResource;
use \App\Models\Platform\Hotel;
use Tests\Unit\Concerns\AssertsResourceOpenApiKeys;
use Tests\Unit\UnitTestCase;

class HotelResourceOpenApiShapeBehaviorTest extends UnitTestCase
{
    use AssertsResourceOpenApiKeys;

    public function test_resource_array_matches_openapi_keys(): void
    {
        $model = new \App\Models\Platform\Hotel();
        $model->forceFill(['id' => 1, 'name' => 'Spec Hotel']);

        if (method_exists($model, 'managers')) {
            $model->setRelation('managers', collect());
        }

        $data = (new \Modules\Chain\Http\Resources\HotelResource($model))->toArray(Request::create('/admin-test', 'GET'));

        $this->assertResourceArrayHasKeys($data, ['id', 'name', 'managers']);
    }
}
