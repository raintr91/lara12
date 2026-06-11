<?php

namespace App\Http\Queries\Traits;

trait ProvidesEntityStats
{
    abstract protected function statsKey(): string;

    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        $query = $this->newQuery();

        return [
            $this->statsKey() => $query ? (int) $query->count() : 0,
        ];
    }
}
