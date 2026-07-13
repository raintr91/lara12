<?php



namespace Modules\Chain\Tests\Unit\Http\Actions;



use \Modules\Chain\Http\Actions\HotelAction;

use \App\Models\Platform\Hotel;

use Tests\Unit\Concerns\AssertsActionRelationshipKeys;

use Tests\Unit\UnitTestCase;



class HotelActionRelationshipsBehaviorTest extends UnitTestCase

{

    use AssertsActionRelationshipKeys;



    public function test_model_defines_spec_relationships(): void

    {

        $this->assertModelDefinesRelationships(\App\Models\Platform\Hotel::class, ['chain', 'managers']);

    }



    public function test_action_uses_entity_model(): void

    {

        $action = new \Modules\Chain\Http\Actions\HotelAction(new \App\Models\Platform\Hotel());



        $this->assertInstanceOf(\Modules\Chain\Http\Actions\HotelAction::class, $action);

    }

}
