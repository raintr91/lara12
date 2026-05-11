<?php

namespace Tests\Unit\Console;

use App\Console\Kernel;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class KernelCoverageTest extends TestCase
{
    public function test_schedule_and_commands_methods_are_executable(): void
    {
        $kernel = new class($this->app, $this->app['events']) extends Kernel {
            public function invokeSchedule(Schedule $schedule): void
            {
                $this->schedule($schedule);
            }

            public function invokeCommands(): void
            {
                $this->commands();
            }
        };

        $kernel->invokeSchedule($this->app->make(Schedule::class));
        $kernel->invokeCommands();

        $this->assertTrue(true);
    }
}
