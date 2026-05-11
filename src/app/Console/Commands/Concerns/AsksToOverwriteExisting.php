<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Filesystem\Filesystem;

trait AsksToOverwriteExisting
{
    /**
     * Ask whether to create a file. If it exists, ask whether to overwrite.
     *
     * Returns one of: 'no', 'keep', 'create', 'overwrite'
     */
    protected function confirmCreateOrOverwrite(
        Filesystem $files,
        string $question,
        string $targetPath,
        bool $defaultYes = true
    ): string {
        if (method_exists($this, 'shouldForceYes') && $this->shouldForceYes()) {
            return $files->exists($targetPath) ? 'overwrite' : 'create';
        }

        if (method_exists($this, 'shouldSkipQuestions') && $this->shouldSkipQuestions()) {
            if (! $defaultYes) {
                return 'no';
            }

            return $files->exists($targetPath) ? 'keep' : 'create';
        }

        if (! $this->confirm($question, $defaultYes)) {
            return 'no';
        }

        if (! $files->exists($targetPath)) {
            return 'create';
        }

        $overwrite = $this->confirm("File exists: {$targetPath}. Overwrite?", false);
        return $overwrite ? 'overwrite' : 'keep';
    }
}
