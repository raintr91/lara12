<?php

namespace Tests\Unit\Policies\Concerns;

use App\Policies\Concerns\ChecksTenantScope;
use Illuminate\Contracts\Auth\Authenticatable;
use Tests\Unit\UnitTestCase;

class ChecksTenantScopeTest extends UnitTestCase
{
    public function test_models_share_tenant_scope(): void
    {
        $checker = new class {
            use ChecksTenantScope;

            public function check(Authenticatable $user, object $model): bool
            {
                return $this->modelsShareTenantScope($user, $model);
            }
        };

        $user = new class implements Authenticatable {
            public int $tenant_id = 1;

            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): int
            {
                return 1;
            }

            public function getAuthPassword(): string
            {
                return '';
            }

            public function getRememberToken(): ?string
            {
                return null;
            }

            public function setRememberToken($value): void
            {
            }

            public function getRememberTokenName(): string
            {
                return '';
            }

            public function getAuthPasswordName(): string
            {
                return 'password';
            }
        };

        $this->assertTrue($checker->check($user, (object) ['tenant_id' => 1]));
        $this->assertFalse($checker->check($user, (object) ['tenant_id' => 2]));
        $this->assertTrue($checker->check($user, (object) ['name' => 'global']));
    }
}
