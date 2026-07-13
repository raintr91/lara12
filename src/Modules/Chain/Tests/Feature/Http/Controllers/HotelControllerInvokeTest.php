<?php

namespace Modules\Chain\Tests\Feature\Http\Controllers;

use \Modules\Chain\Http\Controllers\HotelController;
use \Modules\Chain\Http\Actions\HotelAction;
use \Modules\Chain\Http\Queries\HotelQuery;
use Modules\Chain\Tests\Support\ModuleTestSupport;
use Tests\Unit\UnitTestCase;

class HotelControllerInvokeTest extends UnitTestCase
{
    use ModuleTestSupport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(
            \Modules\Chain\Http\Queries\HotelQuery::class,
            fn () => $this->makeQueryDouble(\Modules\Chain\Http\Queries\HotelQuery::class),
        );
        $this->app->bind(
            \Modules\Chain\Http\Actions\HotelAction::class,
            fn () => $this->makeActionDouble(\Modules\Chain\Http\Actions\HotelAction::class),
        );
    }

    public function test_public_methods_are_invokable(): void
    {
        $controller = $this->app->make(\Modules\Chain\Http\Controllers\HotelController::class);
        $this->invokeAllControllerMethods($controller);
    }
}
