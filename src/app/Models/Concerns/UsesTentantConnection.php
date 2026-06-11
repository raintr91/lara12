<?php

namespace App\Models\Concerns;

/**
 * Eloquent models on a per-chain tenant DB (`mairy-tentant-{id}`).
 * Set database name at runtime via {@see \App\Services\Tenancy\TenantConnectionManager}.
 */
trait UsesTentantConnection
{
    public function getConnectionName(): ?string
    {
        return 'tentant';
    }
}
