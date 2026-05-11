<?php

namespace Tests\Unit\Policies;

use Tests\TestCase;
use App\Policies\BasePolicy;
use App\Models\User;

class BasePolicyTest extends TestCase
{
    protected BasePolicy $policy;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new class extends BasePolicy {
            public function publicSameTenant(User $user, $model): bool
            {
                return $this->sameTenant($user, $model);
            }

            public function publicIsOwner(User $user, $model): bool
            {
                return $this->isOwner($user, $model);
            }

            public function publicHasRole(User $user, string $role): bool
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
        $this->user = new User();
    }

    /**
     * Test before() bypass for super admin.
     */
    public function test_before_allows_super_admin(): void
    {
        $this->user->role = 'super_admin';

        $response = $this->policy->before($this->user, 'anything');

        $this->assertNotNull($response);
        $this->assertTrue($response->allowed());
    }

    /**
     * Test before() returns null for non-super-admin.
     */
    public function test_before_returns_null_for_normal_user(): void
    {
        $this->user->role = 'staff';

        $response = $this->policy->before($this->user, 'anything');

        $this->assertNull($response);
    }

    public function test_before_returns_null_when_user_has_no_role(): void
    {
        $this->user->role = null;

        $response = $this->policy->before($this->user, 'anything');

        $this->assertNull($response);
    }

    /**
     * Test sameTenant helper.
     */
    public function test_same_tenant(): void
    {
        $this->user->tenant_id = 1;

        $sameTenantModel = (object) ['tenant_id' => 1];
        $otherTenantModel = (object) ['tenant_id' => 2];

        $this->assertTrue($this->policy->publicSameTenant($this->user, $sameTenantModel));
        $this->assertFalse($this->policy->publicSameTenant($this->user, $otherTenantModel));
    }

    public function test_same_tenant_true_when_model_has_no_tenant_id(): void
    {
        $this->user->tenant_id = 1;

        $this->assertTrue($this->policy->publicSameTenant($this->user, new \stdClass));
    }

    public function test_same_tenant_true_when_model_tenant_id_is_null(): void
    {
        $this->user->tenant_id = 1;

        $model = (object) ['tenant_id' => null];

        $this->assertTrue($this->policy->publicSameTenant($this->user, $model));
    }

    /**
     * Test isOwner helper.
     */
    public function test_is_owner(): void
    {
        $this->user->id = 10;

        $ownedModel = (object) ['user_id' => 10];
        $notOwnedModel = (object) ['user_id' => 11];

        $this->assertTrue($this->policy->publicIsOwner($this->user, $ownedModel));
        $this->assertFalse($this->policy->publicIsOwner($this->user, $notOwnedModel));
    }

    public function test_is_owner_false_when_model_has_no_user_id(): void
    {
        $this->user->id = 10;

        $this->assertFalse($this->policy->publicIsOwner($this->user, new \stdClass));
    }

    /**
     * Test hasRole helper.
     */
    public function test_has_role(): void
    {
        $this->user->role = 'manager';

        $this->assertTrue($this->policy->publicHasRole($this->user, 'manager'));
        $this->assertFalse($this->policy->publicHasRole($this->user, 'staff'));
    }

    /**
     * Test allow helper returns allowed response.
     */
    public function test_allow_helper(): void
    {
        $response = $this->policy->publicAllow('Allowed');

        $this->assertTrue($response->allowed());
        $this->assertSame('Allowed', $response->message());
    }

    public function test_allow_helper_uses_default_message(): void
    {
        $response = $this->policy->publicAllow();

        $this->assertTrue($response->allowed());
        $this->assertSame('Allowed', $response->message());
    }

    /**
     * Test deny helper returns denied response.
     */
    public function test_deny_helper(): void
    {
        $response = $this->policy->publicDeny('Denied');

        $this->assertFalse($response->allowed());
        $this->assertSame('Denied', $response->message());
    }

    public function test_deny_helper_uses_default_message(): void
    {
        $response = $this->policy->publicDeny();

        $this->assertFalse($response->allowed());
        $this->assertSame('This action is unauthorized.', $response->message());
    }
}
