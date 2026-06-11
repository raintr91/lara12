<?php

namespace App\Policies\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;

trait ChecksTenantScope
{
    /**
     * Optional global bypass — override in module policies (e.g. super_admin).
     */
    protected function bypassAuthorization(Authenticatable $user, string $ability): ?bool
    {
        return null;
    }

    protected function modelsShareTenantScope(Authenticatable $user, object $model): bool
    {
        if (! property_exists($model, 'tenant_id')) {
            return true;
        }

        $userTenantId = $user->tenant_id ?? null;

        return $userTenantId !== null && $userTenantId === $model->tenant_id;
    }
}
