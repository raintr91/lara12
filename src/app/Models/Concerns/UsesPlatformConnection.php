<?php

namespace App\Models\Concerns;

/**
 * Eloquent models on the platform DB. See {@see \App\Models\PlatformModel}.
 */
trait UsesPlatformConnection
{
    public function getConnectionName(): ?string
    {
        return 'platform';
    }
}
