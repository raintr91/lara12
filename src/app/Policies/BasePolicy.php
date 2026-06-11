<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;

abstract class BasePolicy
{
    /**
     * Global bypass hook — return true/false to short-circuit, or null to continue.
     */
    public function before(Authenticatable $user, string $ability): ?Response
    {
        $bypass = $this->bypassAuthorization($user, $ability);

        if ($bypass === true) {
            return $this->allow();
        }

        if ($bypass === false) {
            return $this->deny();
        }

        return null;
    }

    /**
     * Override in module policies when a role should bypass all checks.
     */
    protected function bypassAuthorization(Authenticatable $user, string $ability): ?bool
    {
        return null;
    }

    protected function isOwner(Authenticatable $user, object $model): bool
    {
        return property_exists($model, 'user_id')
            && ($user->getAuthIdentifier() ?? null) === $model->user_id;
    }

    protected function hasRole(Authenticatable $user, string $role): bool
    {
        return property_exists($user, 'role') && $user->role === $role;
    }

    protected function allow(string $message = 'Allowed'): Response
    {
        return Response::allow($message);
    }

    protected function deny(string $message = 'This action is unauthorized.'): Response
    {
        return Response::deny($message);
    }
}
