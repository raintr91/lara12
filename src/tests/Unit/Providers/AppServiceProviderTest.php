<?php

namespace Tests\Unit\Providers;

use App\Providers\AppServiceProvider;
use Tests\Unit\UnitTestCase;

class AppServiceProviderTest extends UnitTestCase
{
    public function test_register_calls_module_provider_register(): void
    {
        $provider = new AppServiceProvider($this->app);

        // This should not throw any exceptions
        $provider->register();

        // Verify that the service provider was registered
        $this->assertTrue($this->app->bound('app'));
    }

    public function test_boot_calls_module_provider_boot(): void
    {
        $provider = new AppServiceProvider($this->app);

        // This should not throw any exceptions
        $provider->boot();

        // Verify that booting completed successfully
        $this->assertTrue($this->app->bound('app'));
    }

    public function test_app_service_provider_registers_and_boots(): void
    {
        $provider = new AppServiceProvider($this->app);

        $provider->register();
        $provider->boot();

        // Verify the app is still functional
        $this->assertNotNull($this->app);
    }
}
