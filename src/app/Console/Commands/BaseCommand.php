<?php

namespace App\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\Command;

abstract class BaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * Default implementation returns 0. Override in concrete commands.
     */
    public function handle()
    {
        return 0;
    }

    /**
     * Helper method to output info message.
     */
    public function info($string, $verbosity = null): void
    {
        parent::info($string, $verbosity);
    }

    /**
     * Helper method to output error message.
     */
    public function error($string, $verbosity = null): void
    {
        parent::error($string, $verbosity);
    }

    /**
     * Helper method to output warning message.
     */
    public function warn($string, $verbosity = null): void
    {
        parent::warn($string, $verbosity);
    }

    protected function shouldSkipQuestions(): bool
    {
        return $this->hasOption('skip-questions') && (bool) $this->option('skip-questions');
    }

    protected function shouldForceYes(): bool
    {
        return $this->hasOption('yes') && (bool) $this->option('yes');
    }

    protected function resolveYesNoOption(string $name): ?bool
    {
        if (! $this->hasOption($name)) {
            return null;
        }

        $value = $this->option($name);
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));
        if (in_array($value, ['yes', 'y', '1', 'true'], true)) {
            return true;
        }

        if (in_array($value, ['no', 'n', '0', 'false'], true)) {
            return false;
        }

        return null;
    }

    protected function askYesNo(string $optionName, string $question, bool $default): bool
    {
        if ($this->shouldForceYes()) {
            return true;
        }

        $resolved = $this->resolveYesNoOption($optionName);
        if ($resolved !== null) {
            return $resolved;
        }

        if ($this->shouldSkipQuestions() || ! $this->input->isInteractive()) {
            return $default;
        }

        return $this->confirm($question, $default);
    }

    protected function askCreateOrOverwrite(
        Filesystem $files,
        string $optionName,
        string $question,
        string $targetPath,
        bool $defaultYes = true
    ): string {
        if ($this->shouldForceYes()) {
            return $files->exists($targetPath) ? 'overwrite' : 'create';
        }

        $resolved = $this->resolveYesNoOption($optionName);
        if ($resolved === false) {
            return 'no';
        }

        if ($resolved === true) {
            return $files->exists($targetPath) ? 'overwrite' : 'create';
        }

        if ($this->shouldSkipQuestions() || ! $this->input->isInteractive()) {
            if (! $defaultYes) {
                return 'no';
            }

            return $files->exists($targetPath) ? 'keep' : 'create';
        }

        if (! method_exists($this, 'confirmCreateOrOverwrite')) {
            throw new \RuntimeException(static::class . ' must use AsksToOverwriteExisting trait to call askCreateOrOverwrite.');
        }

        return $this->confirmCreateOrOverwrite($files, $question, $targetPath, $defaultYes);
    }
}
