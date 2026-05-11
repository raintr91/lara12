<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

abstract class BasePolicy
{
    /**
     * Global bypass (super admin)
     */
    public function before(User $user, $ability)
    {
        if ($user->role === 'super_admin') {
            return $this->allow();
        }

        return null;
    }

    protected function sameTenant(User $user, $model): bool
    {
        return !isset($model->tenant_id)
            || $user->tenant_id === $model->tenant_id;
    }

    protected function isOwner(User $user, $model): bool
    {
        return isset($model->user_id)
            && $user->id === $model->user_id;
    }

    protected function hasRole(User $user, string $role): bool
    {
        return $user->role === $role;
    }

    protected function allow(string $message = 'Allowed')
    {
        return Response::allow($message);
    }

    protected function deny(string $message = 'This action is unauthorized.')
    {
        return Response::deny($message);
    }
}
