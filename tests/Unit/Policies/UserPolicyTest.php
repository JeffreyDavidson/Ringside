<?php

declare(strict_types=1);

use App\Enums\Users\UserStatus;
use App\Models\Users\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;

/**
 * Unit tests for UserPolicy authorization logic.
 *
 * Tests the global Gate hook used for administrator bypass
 * and validates that basic users are properly restricted.
 *
 * @see UserPolicy
 */
describe('UserPolicy global Gate hook', function () {
    beforeEach(function () {
        $this->policy = new UserPolicy();
        $this->administrator = administrator();
        $this->basicUser = basicUser();
    });

    test('global Gate hook allows administrators for any ability', function () {
        $result = Gate::forUser($this->administrator)->raw('viewAny');

        expect($result)->toBeTrue();
    });

    test('global Gate hook returns null for basic users', function () {
        $result = Gate::forUser($this->basicUser)->raw('viewAny');

        expect($result)->toBeNull();
    });

    test('global Gate hook allows administrators for all abilities', function () {
        $abilities = ['viewAny', 'view', 'create', 'update', 'delete', 'restore'];

        foreach ($abilities as $ability) {
            $result = Gate::forUser($this->administrator)->raw($ability);
            expect($result)->toBeTrue("Administrator should be allowed for {$ability}");
        }
    });

    test('global Gate hook returns null for basic users on all abilities', function () {
        $abilities = ['viewAny', 'view', 'create', 'update', 'delete', 'restore'];

        foreach ($abilities as $ability) {
            $result = Gate::forUser($this->basicUser)->raw($ability);
            expect($result)->toBeNull("Basic user should get null for {$ability}");
        }
    });

    test('global Gate hook works for user-specific abilities', function () {
        expect(Gate::forUser($this->administrator)->raw('viewProfile'))->toBeTrue();
        expect(Gate::forUser($this->administrator)->raw('changePassword'))->toBeTrue();
        expect(Gate::forUser($this->administrator)->raw('manageRoles'))->toBeTrue();
        expect(Gate::forUser($this->administrator)->raw('deactivate'))->toBeTrue();

        expect(Gate::forUser($this->basicUser)->raw('viewProfile'))->toBeNull();
        expect(Gate::forUser($this->basicUser)->raw('changePassword'))->toBeNull();
        expect(Gate::forUser($this->basicUser)->raw('manageRoles'))->toBeNull();
        expect(Gate::forUser($this->basicUser)->raw('deactivate'))->toBeNull();
    });
});

describe('UserPolicy individual methods', function () {
    beforeEach(function () {
        $this->policy = new UserPolicy();
        $this->basicUser = basicUser();
    });

    test('viewAny method returns false for basic users', function () {
        $result = $this->policy->viewAny($this->basicUser);

        expect($result)->toBeFalse();
    });

    test('view method returns false for basic users', function () {
        $result = $this->policy->view($this->basicUser, $this->basicUser);

        expect($result)->toBeFalse();
    });

    test('create method returns false for basic users', function () {
        $result = $this->policy->create($this->basicUser);

        expect($result)->toBeFalse();
    });

    test('update method returns false for basic users', function () {
        $result = $this->policy->update($this->basicUser, $this->basicUser);

        expect($result)->toBeFalse();
    });

    test('delete method returns false for basic users', function () {
        $result = $this->policy->delete($this->basicUser, $this->basicUser);

        expect($result)->toBeFalse();
    });

    test('restore method returns false for basic users', function () {
        $result = $this->policy->restore($this->basicUser, $this->basicUser);

        expect($result)->toBeFalse();
    });
});

describe('UserPolicy integration with Gate facade', function () {
    test('Gate allows administrators through global Gate hook', function () {
        actingAs(administrator());
        $targetUser = basicUser();

        expect(Gate::allows('viewAny', User::class))->toBeTrue();
        expect(Gate::allows('view', $targetUser))->toBeTrue();
        expect(Gate::allows('create', User::class))->toBeTrue();
        expect(Gate::allows('update', $targetUser))->toBeTrue();
        expect(Gate::allows('delete', $targetUser))->toBeTrue();
        expect(Gate::allows('restore', $targetUser))->toBeTrue();
    });

    test('Gate denies basic users after global Gate hook returns null', function () {
        actingAs(basicUser());
        $targetUser = administrator();

        expect(Gate::denies('viewAny', User::class))->toBeTrue();
        expect(Gate::denies('view', $targetUser))->toBeTrue();
        expect(Gate::denies('create', User::class))->toBeTrue();
        expect(Gate::denies('update', $targetUser))->toBeTrue();
        expect(Gate::denies('delete', $targetUser))->toBeTrue();
        expect(Gate::denies('restore', $targetUser))->toBeTrue();
    });

    test('Gate works with specific user instances', function () {
        $user = User::factory()->create();

        actingAs(administrator());
        expect(Gate::allows('view', $user))->toBeTrue();
        expect(Gate::allows('update', $user))->toBeTrue();
        expect(Gate::allows('delete', $user))->toBeTrue();

        actingAs(basicUser());
        expect(Gate::denies('view', $user))->toBeTrue();
        expect(Gate::denies('update', $user))->toBeTrue();
        expect(Gate::denies('delete', $user))->toBeTrue();
    });

    // NOTE: Gate integration testing moved to Feature tests for proper application context
    // test('Gate supports user management operations through global Gate hook', function () {
    //     actingAs(administrator());
    //
    //     // User management operations should be allowed for administrators
    //     expect(Gate::allows('viewProfile', User::class))->toBeTrue();
    //     expect(Gate::allows('changePassword', User::class))->toBeTrue();
    //     expect(Gate::allows('manageRoles', User::class))->toBeTrue();
    //     expect(Gate::allows('deactivate', User::class))->toBeTrue();
    //
    //     actingAs(basicUser());
    //
    //     // Basic users should be denied these operations
    //     expect(Gate::denies('viewProfile', User::class))->toBeTrue();
    //     expect(Gate::denies('changePassword', User::class))->toBeTrue();
    //     expect(Gate::denies('manageRoles', User::class))->toBeTrue();
    //     expect(Gate::denies('deactivate', User::class))->toBeTrue();
    // });
});

describe('UserPolicy method signatures', function () {
    test('administrator bypass is not duplicated on the policy', function () {
        expect(get_class_methods($this->policy))->not->toContain('before');
    });

    test('policy methods have correct signatures', function () {
        $methods = ['viewAny', 'view', 'create', 'update', 'delete', 'restore'];

        foreach ($methods as $method) {
            $reflection = new ReflectionMethod(UserPolicy::class, $method);

            $expectedParameterCount = in_array($method, ['viewAny', 'create'], true) ? 1 : 2;

            expect($reflection->getParameters())->toHaveCount($expectedParameterCount);
            expect(reflectionTypeName($reflection->getParameters()[0]))->toBe(User::class);
            if ($expectedParameterCount === 2) {
                expect(reflectionTypeName($reflection->getParameters()[1]))->toBe(User::class);
            }
            expect(reflectionReturnTypeName($reflection))->toBe('bool');
        }
    });
});

describe('UserPolicy business context', function () {
    beforeEach(function () {
        $this->policy = new UserPolicy();
    });

    test('policy supports user management operations via the global Gate hook', function () {
        // These operations aren't explicitly defined in the policy
        // but should be allowed for administrators via the global Gate hook
        $userOperations = [
            'viewProfile', 'changePassword', 'manageRoles', 'deactivate',
            'activate', 'resetPassword', 'changeRole', 'viewAuditLog',
        ];

        foreach ($userOperations as $operation) {
            expect(Gate::forUser(administrator())->raw($operation))
                ->toBeTrue("Administrator should be able to {$operation} users");

            expect(Gate::forUser(basicUser())->raw($operation))
                ->toBeNull("Basic user should continue to individual checks for {$operation}");
        }
    });

    test('policy works with different user roles', function () {
        $adminUser = User::factory()->administrator()->create();
        $basicUser = User::factory()->create();

        // Both user types should follow same authorization rules
        expect(Gate::forUser(administrator())->allows('view', $adminUser))->toBeTrue();
        expect(Gate::forUser(administrator())->allows('view', $basicUser))->toBeTrue();

        expect(Gate::forUser(basicUser())->denies('view', $adminUser))->toBeTrue();
        expect(Gate::forUser(basicUser())->denies('view', $basicUser))->toBeTrue();
    });

    test('policy works with different user statuses', function () {
        $activeUser = User::factory()->create(['status' => UserStatus::Active]);
        $inactiveUser = User::factory()->create(['status' => UserStatus::Inactive]);
        $unverifiedUser = User::factory()->create(['status' => UserStatus::Unverified]);

        // All user statuses should follow same authorization rules
        expect(Gate::forUser(administrator())->allows('update', $activeUser))->toBeTrue();
        expect(Gate::forUser(administrator())->allows('update', $inactiveUser))->toBeTrue();
        expect(Gate::forUser(administrator())->allows('update', $unverifiedUser))->toBeTrue();

        expect(Gate::forUser(basicUser())->denies('update', $activeUser))->toBeTrue();
        expect(Gate::forUser(basicUser())->denies('update', $inactiveUser))->toBeTrue();
        expect(Gate::forUser(basicUser())->denies('update', $unverifiedUser))->toBeTrue();
    });

    test('policy maintains consistency with authentication system', function () {
        $admin = administrator();
        $basic = basicUser();

        // Verify the policy respects the user's isAdministrator method
        expect($admin->role->isAdministrator())->toBeTrue();
        expect($basic->role->isAdministrator())->toBeFalse();

        expect(Gate::forUser($admin)->raw('any-operation'))->toBeTrue();
        expect(Gate::forUser($basic)->raw('any-operation'))->toBeNull();
    });
});

describe('UserPolicy edge cases and security', function () {
    beforeEach(function () {
        $this->policy = new UserPolicy();
    });

    test('policy is consistent across multiple instances', function () {
        $policy1 = new UserPolicy();
        $policy2 = new UserPolicy();

        expect($policy1->viewAny(basicUser()))->toBe($policy2->viewAny(basicUser()));
    });

    test('policy is stateless', function () {
        // Multiple calls should return same results
        expect($this->policy->viewAny(basicUser()))->toBeFalse();
        expect($this->policy->viewAny(basicUser()))->toBeFalse();

        expect(Gate::forUser(administrator())->raw('create'))->toBeTrue();
        expect(Gate::forUser(administrator())->raw('create'))->toBeTrue();
    });

    test('policy correctly identifies administrator privileges', function () {
        $admin = administrator();
        $basic = basicUser();

        // Administrator should consistently pass the global Gate hook
        $abilities = ['create', 'read', 'update', 'delete', 'custom', 'manage', 'any'];

        foreach ($abilities as $ability) {
            expect(Gate::forUser($admin)->raw($ability))->toBeTrue();
            expect(Gate::forUser($basic)->raw($ability))->toBeNull();
        }
    });
});
