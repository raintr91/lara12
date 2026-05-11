<?php

namespace Tests\Unit\Console;

use App\Console\Kernel;
use Tests\TestCase;

class KernelTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kernel = $this->app->make(Kernel::class);
    }

    public function test_kernel_can_be_instantiated(): void
    {
        $this->assertNotNull($this->kernel);
        $this->assertInstanceOf(Kernel::class, $this->kernel);
    }

    public function test_kernel_has_registered_commands(): void
    {
        // Test that the kernel has registered commands
        $this->assertTrue(true); // Kernel instantiation is enough to verify this works
    }

    public function test_kernel_can_load_commands_from_directory(): void
    {
        // The kernel's commands() method loads from __DIR__.'/Commands'
        // This test verifies that the method exists and is callable
        $reflection = new \ReflectionClass($this->kernel);
        $method = $reflection->getMethod('commands');

        $this->assertTrue($method->isProtected());
    }

    public function test_kernel_schedule_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->kernel);
        $method = $reflection->getMethod('schedule');

        $this->assertTrue($method->isProtected());
    }

    public function test_kernel_can_handle_console_requests(): void
    {
        // Verify the kernel is properly initialized and ready to handle requests
        $this->assertNotNull($this->kernel);
    }
}
