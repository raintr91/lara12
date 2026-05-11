<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\BaseCommand;
use Illuminate\Filesystem\Filesystem;
use ReflectionProperty;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class BaseCommandBehaviorTest extends TestCase
{
    public function test_handle_and_output_helpers_are_callable(): void
    {
        $command = $this->makeCommand();

        $this->assertSame(0, $command->handle());

        $command->info('info');
        $command->warn('warn');
        $command->error('error');

        $this->assertTrue(true);
    }

    public function test_resolve_yes_no_option_parses_supported_values(): void
    {
        $command = $this->makeCommand();

        $command->setOptionValue('answer', 'yes');
        $this->assertTrue($command->resolveYesNoOptionPublic('answer'));

        $command->setOptionValue('answer', '0');
        $this->assertFalse($command->resolveYesNoOptionPublic('answer'));

        $command->setOptionValue('answer', true);
        $this->assertTrue($command->resolveYesNoOptionPublic('answer'));

        $command->setOptionValue('answer', 'maybe');
        $this->assertNull($command->resolveYesNoOptionPublic('answer'));

        $command->setOptionValue('answer', '');
        $this->assertNull($command->resolveYesNoOptionPublic('answer'));
    }

    public function test_ask_yes_no_covers_force_resolved_default_and_confirm_branches(): void
    {
        $command = $this->makeCommand();

        $command->setOptionValue('yes', true);
        $this->assertTrue($command->askYesNoPublic('opt', 'q?', false));

        $command->setOptionValue('yes', false);
        $command->setOptionValue('opt', 'no');
        $this->assertFalse($command->askYesNoPublic('opt', 'q?', true));

        $command->setOptionValue('opt', null);
        $command->setOptionValue('skip-questions', true);
        $this->assertTrue($command->askYesNoPublic('opt', 'q?', true));

        $command->setOptionValue('skip-questions', false);
        $command->setInteractive(false);
        $this->assertFalse($command->askYesNoPublic('opt', 'q?', false));

        $command->setInteractive(true);
        $command->answers = [false];
        $this->assertFalse($command->askYesNoPublic('opt', 'q?', true));
    }

    public function test_ask_create_or_overwrite_covers_all_paths_and_runtime_exception(): void
    {
        $files = new Filesystem();
        $missingPath = storage_path('framework/cache/base-command-missing-' . uniqid() . '.txt');
        $existingPath = storage_path('framework/cache/base-command-existing-' . uniqid() . '.txt');
        $files->put($existingPath, 'x');

        $command = $this->makeCommand();

        $command->setOptionValue('yes', true);
        $this->assertSame('create', $command->askCreateOrOverwritePublic($files, 'opt', 'q?', $missingPath));
        $this->assertSame('overwrite', $command->askCreateOrOverwritePublic($files, 'opt', 'q?', $existingPath));

        $command->setOptionValue('yes', false);
        $command->setOptionValue('opt', 'no');
        $this->assertSame('no', $command->askCreateOrOverwritePublic($files, 'opt', 'q?', $missingPath));

        $command->setOptionValue('opt', 'yes');
        $this->assertSame('create', $command->askCreateOrOverwritePublic($files, 'opt', 'q?', $missingPath));
        $this->assertSame('overwrite', $command->askCreateOrOverwritePublic($files, 'opt', 'q?', $existingPath));

        $command->setOptionValue('opt', null);
        $command->setOptionValue('skip-questions', true);
        $this->assertSame('no', $command->askCreateOrOverwritePublic($files, 'opt', 'q?', $missingPath, false));
        $this->assertSame('keep', $command->askCreateOrOverwritePublic($files, 'opt', 'q?', $existingPath, true));

        $command->setOptionValue('skip-questions', false);
        $command->setInteractive(false);
        $this->assertSame('create', $command->askCreateOrOverwritePublic($files, 'opt', 'q?', $missingPath, true));

        $command->setInteractive(true);
        $command->answers = [true, true];
        $this->assertSame('overwrite', $command->askCreateOrOverwritePublic($files, 'opt', 'q?', $existingPath, true));

        $noTrait = new class extends BaseCommand {
            public array $optionValues = ['yes' => false, 'skip-questions' => false, 'x' => null];
            public array $answers = [true];

            public function hasOption($name): bool
            {
                return array_key_exists((string) $name, $this->optionValues);
            }

            public function option($key = null): mixed
            {
                return $this->optionValues[$key] ?? null;
            }

            public function confirm($question, $default = false): bool
            {
                return array_shift($this->answers) ?? $default;
            }

            public function askCreateOrOverwritePublic(Filesystem $files, string $optionName, string $question, string $targetPath, bool $defaultYes = true): string
            {
                return $this->askCreateOrOverwrite($files, $optionName, $question, $targetPath, $defaultYes);
            }
        };
        $this->setInputInteractive($noTrait, true);

        $this->expectException(\RuntimeException::class);
        $noTrait->askCreateOrOverwritePublic($files, 'x', 'q?', $missingPath, true);
    }

    private function makeCommand(): BaseCommand
    {
        $command = new class extends BaseCommand {
            use \App\Console\Commands\Concerns\AsksToOverwriteExisting;

            public array $optionValues = [
                'yes' => false,
                'skip-questions' => false,
                'answer' => null,
                'opt' => null,
            ];

            public array $answers = [true];

            public function hasOption($name): bool
            {
                return array_key_exists((string) $name, $this->optionValues);
            }

            public function option($key = null): mixed
            {
                return $this->optionValues[$key] ?? null;
            }

            public function confirm($question, $default = false): bool
            {
                return array_shift($this->answers) ?? $default;
            }

            public function setOptionValue(string $name, mixed $value): void
            {
                $this->optionValues[$name] = $value;
            }

            public function resolveYesNoOptionPublic(string $name): ?bool
            {
                return $this->resolveYesNoOption($name);
            }

            public function askYesNoPublic(string $optionName, string $question, bool $default): bool
            {
                return $this->askYesNo($optionName, $question, $default);
            }

            public function askCreateOrOverwritePublic(Filesystem $files, string $optionName, string $question, string $targetPath, bool $defaultYes = true): string
            {
                return $this->askCreateOrOverwrite($files, $optionName, $question, $targetPath, $defaultYes);
            }

            public function setInteractive(bool $interactive): void
            {
                $input = new ArrayInput([]);
                $input->setInteractive($interactive);
                $prop = new ReflectionProperty(\Illuminate\Console\Command::class, 'input');
                $prop->setAccessible(true);
                $prop->setValue($this, $input);
            }
        };

        $this->setInputInteractive($command, true);

        return $command;
    }

    private function setInputInteractive(BaseCommand $command, bool $interactive): void
    {
        $input = new ArrayInput([]);
        $input->setInteractive($interactive);
        $inputProp = new ReflectionProperty(\Illuminate\Console\Command::class, 'input');
        $inputProp->setAccessible(true);
        $inputProp->setValue($command, $input);

        $outputProp = new ReflectionProperty(\Illuminate\Console\Command::class, 'output');
        $outputProp->setAccessible(true);
        $outputProp->setValue($command, new BufferedOutput());
    }
}
