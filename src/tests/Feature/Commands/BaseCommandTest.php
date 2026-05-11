<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Console\Commands\BaseCommand;

class BaseCommandTest extends TestCase
{
    /**
     * Test command can be instantiated.
     */
    public function test_command_can_be_instantiated(): void
    {
        $command = new class extends BaseCommand {
            protected $signature = 'test:command';
            protected $description = 'Test command';

            public function handle(): int
            {
                return 0;
            }
        };

        $this->assertInstanceOf(BaseCommand::class, $command);
    }

    /**
     * Test command extends Illuminate Command.
     */
    public function test_command_extends_illuminate_command(): void
    {
        $command = new class extends BaseCommand {
            protected $signature = 'test:command';

            public function handle(): int
            {
                return 0;
            }
        };

        $this->assertInstanceOf(\Illuminate\Console\Command::class, $command);
    }

    /**
     * Test command handle method returns integer.
     */
    public function test_command_handle_returns_integer(): void
    {
        $command = new class extends BaseCommand {
            public function handle(): int
            {
                return 0;
            }
        };

        $result = $command->handle();

        $this->assertIsInt($result);
    }
}
