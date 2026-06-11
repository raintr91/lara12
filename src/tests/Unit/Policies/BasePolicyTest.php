<?php

namespace Tests\Unit\Policies;

use App\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;
use Tests\Unit\UnitTestCase;

class BasePolicyTest extends UnitTestCase
{
    protected BasePolicy $policy;

    protected object $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new class extends BasePolicy {
            public function publicIsOwner(Authenticatable $user, object $model): bool
            {
                return $this->isOwner($user, $model);
            }

            public function publicHasRole(Authenticatable $user, string $role): bool
            {
                return $this->hasRole($user, $role);
            }

            public function publicAllow(string $message = 'Allowed')
            {
                return $this->allow($message);
            }

            public function publicDeny(string $message = 'This action is unauthorized.')
            {
                return $this->deny($message);
            }
        };

        $this->user = new class implements Authenticatable {
            public int $id = 0;

            public string $role = 'staff';

            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): int
            {
                return $this->id;
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
    }

    public function test_before_returns_null_when_no_bypass(): void
    {
        $response = $this->policy->before($this->user, 'anything');

        $this->assertNull($response);
    }

    public function test_before_allows_when_bypass_returns_true(): void
    {
        $policy = new class extends BasePolicy {
            protected function bypassAuthorization(Authenticatable $user, string $ability): ?bool
            {
                return true;
            }
        };

        $response = $policy->before($this->user, 'anything');

        $this->assertNotNull($response);
        $this->assertTrue($response->allowed());
    }

    public function test_is_owner(): void
    {
        $this->user->id = 10;

        $ownedModel = (object) ['user_id' => 10];
        $notOwnedModel = (object) ['user_id' => 11];

        $this->assertTrue($this->policy->publicIsOwner($this->user, $ownedModel));
        $this->assertFalse($this->policy->publicIsOwner($this->user, $notOwnedModel));
    }

    public function test_has_role(): void
    {
        $this->user->role = 'manager';

        $this->assertTrue($this->policy->publicHasRole($this->user, 'manager'));
        $this->assertFalse($this->policy->publicHasRole($this->user, 'staff'));
    }

    public function test_allow_helper(): void
    {
        $response = $this->policy->publicAllow('Allowed');

        $this->assertTrue($response->allowed());
    }

    public function test_deny_helper(): void
    {
        $response = $this->policy->publicDeny('Denied');

        $this->assertFalse($response->allowed());
    }
}
