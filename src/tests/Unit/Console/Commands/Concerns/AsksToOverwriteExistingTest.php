<?php

namespace Tests\Unit\Console\Commands\Concerns;

use App\Console\Commands\Concerns\AsksToOverwriteExisting;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class AsksToOverwriteExistingTest extends TestCase
{
    public function test_force_yes_and_skip_questions_short_circuit_branches(): void
    {
        $files = new Filesystem();
        $existing = storage_path('framework/cache/existing-short-' . uniqid() . '.txt');
        $files->put($existing, 'x');

        $forceYesSubject = new class {
            use AsksToOverwriteExisting;

            public function shouldForceYes(): bool
            {
                return true;
            }

            public function run(Filesystem $files, string $path): string
            {
                return $this->confirmCreateOrOverwrite($files, 'q', $path, true);
            }
        };

        $skipSubject = new class {
            use AsksToOverwriteExisting;

            public function shouldSkipQuestions(): bool
            {
                return true;
            }

            public function run(Filesystem $files, string $path, bool $defaultYes): string
            {
                return $this->confirmCreateOrOverwrite($files, 'q', $path, $defaultYes);
            }
        };

        $this->assertSame('overwrite', $forceYesSubject->run($files, $existing));
        $this->assertSame('keep', $skipSubject->run($files, $existing, true));
        $this->assertSame('no', $skipSubject->run($files, $existing, false));

        $files->delete($existing);
    }

    public function test_returns_no_when_user_declines_initial_confirmation(): void
    {
        $subject = new class {
            use AsksToOverwriteExisting;

            public array $answers = [false];

            public function confirm($question, $default = false)
            {
                return array_shift($this->answers);
            }

            public function run(Filesystem $files, string $path): string
            {
                return $this->confirmCreateOrOverwrite($files, 'q', $path, true);
            }
        };

        $this->assertSame('no', $subject->run(new Filesystem(), storage_path('framework/cache/not-used')));
    }

    public function test_returns_create_when_file_does_not_exist(): void
    {
        $subject = new class {
            use AsksToOverwriteExisting;

            public array $answers = [true];

            public function confirm($question, $default = false)
            {
                return array_shift($this->answers);
            }

            public function run(Filesystem $files, string $path): string
            {
                return $this->confirmCreateOrOverwrite($files, 'q', $path, true);
            }
        };

        $this->assertSame('create', $subject->run(new Filesystem(), storage_path('framework/cache/missing-' . uniqid())));
    }

    public function test_returns_keep_or_overwrite_when_file_exists(): void
    {
        $files = new Filesystem();
        $path = storage_path('framework/cache/existing-' . uniqid() . '.txt');
        $files->put($path, 'x');

        $keepSubject = new class {
            use AsksToOverwriteExisting;

            public array $answers = [true, false];

            public function confirm($question, $default = false)
            {
                return array_shift($this->answers);
            }

            public function run(Filesystem $files, string $path): string
            {
                return $this->confirmCreateOrOverwrite($files, 'q', $path, true);
            }
        };

        $overwriteSubject = new class {
            use AsksToOverwriteExisting;

            public array $answers = [true, true];

            public function confirm($question, $default = false)
            {
                return array_shift($this->answers);
            }

            public function run(Filesystem $files, string $path): string
            {
                return $this->confirmCreateOrOverwrite($files, 'q', $path, true);
            }
        };

        $this->assertSame('keep', $keepSubject->run($files, $path));
        $this->assertSame('overwrite', $overwriteSubject->run($files, $path));

        $files->delete($path);
    }
}
