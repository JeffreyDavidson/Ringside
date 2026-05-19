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
 * Tests the before hook pattern used for administrator bypass
 * and validates that basic users are properly restricted.
 *
 * @see UserPolicy
 */
describe('UserPolicy before hook', function () {
    beforeEach(function () {
        $this->policy = new UserPolicy();
        $this->administrator = administrator();
        $this->basicUser = basicUser();
    });

    test('before hook allows administrators for any ability', function () {
        $result = $this->policy->before($this->administrator, 'viewList');

        expect($result)->toBeTrue();
    });

    test('before hook returns null for basic users', function () {
        $result = $this->policy->before($this->basicUser, 'viewList');

        expect($result)->toBeNull();
    });

    test('before hook allows administrators for all abilities', function () {
        $abilities = ['viewList', 'view', 'create', 'update', 'delete', 'restore'];

        foreach ($abilities as $ability) {
            $result = $this->policy->before($this->administrator, $ability);
            expect($result)->toBeTrue("Administrator should be allowed for {$ability}");
        }
    });

    test('before hook returns null for basic users on all abilities', function () {
        $abilities = ['viewList', 'view', 'create', 'update', 'delete', 'restore'];

        foreach ($abilities as $ability) {
            $result = $this->policy->before($this->basicUser, $ability);
            expect($result)->toBeNull("Basic user should get null for {$ability}");
        }
    });

    test('before hook works for user-specific abilities', function () {
        expect($this->policy->before($this->administrator, 'viewProfile'))->toBeTrue();
        expect($this->policy->before($this->administrator, 'changePassword'))->toBeTrue();
        expect($this->policy->before($this->administrator, 'manageRoles'))->toBeTrue();
        expect($this->policy->before($this->administrator, 'deactivate'))->toBeTrue();

        expect($this->policy->before($this->basicUser, 'viewProfile'))->toBeNull();
        expect($this->policy->before($this->basicUser, 'changePassword'))->toBeNull();
        expect($this->policy->before($this->basicUser, 'manageRoles'))->toBeNull();
        expect($this->policy->before($this->basicUser, 'deactivate'))->toBeNull();
    });
});

describe('UserPolicy individual methods', function () {
    beforeEach(function () {
        $this->policy = new UserPolicy();
        $this->basicUser = basicUser();
    });

    test('viewList method returns false for basic users', function () {
        $result = $this->policy->viewList($this->basicUser);

        expect($result)->toBeFalse();
    });

    test('view method returns false for basic users', function () {
        $result = $this->policy->view($this->basicUser);

        expect($result)->toBeFalse();
    });

    test('create method returns false for basic users', function () {
        $result = $this->policy->create($this->basicUser);

        expect($result)->toBeFalse();
    });

    test('update method returns false for basic users', function () {
        $result = $this->policy->update($this->basicUser);

        expect($result)->toBeFalse();
    });

    test('delete method returns false for basic users', function () {
        $result = $this->policy->delete($this->basicUser);

        expect($result)->toBeFalse();
    });

    test('restore method returns false for basic users', function () {
        $result = $this->policy->restore($this->basicUser);

        expect($result)->toBeFalse();
    });
});

describe('UserPolicy integration with Gate facade', function () {
    test('Gate allows administrators through before hook', function () {
        actingAs(administrator());

        expect(Gate::allows('viewList', User::class))->toBeTrue();
        expect(Gate::allows('view', User::class))->toBeTrue();
        expect(Gate::allows('create', User::class))->toBeTrue();
        expect(Gate::allows('update', User::class))->toBeTrue();
        expect(Gate::allows('delete', User::class))->toBeTrue();
        expect(Gate::allows('restore', User::class))->toBeTrue();
    });

    test('Gate denies basic users after before hook returns null', function () {
        actingAs(basicUser());

        expect(Gate::denies('viewList', User::class))->toBeTrue();
        expect(Gate::denies('view', User::class))->toBeTrue();
        expect(Gate::denies('create', User::class))->toBeTrue();
        expect(Gate::denies('update', User::class))->toBeTrue();
        expect(Gate::denies('delete', User::class))->toBeTrue();
        expect(Gate::denies('restore', User::class))->toBeTrue();
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
    // test('Gate supports user management operations through before hook', function () {
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
    test('policy class exists and has required methods', function () {
        expect(class_exists(UserPolicy::class))->toBeTrue();
        expect(method_exists(UserPolicy::class, 'before'))->toBeTrue();
        expect(method_exists(UserPolicy::class, 'viewList'))->toBeTrue();
        expect(method_exists(UserPolicy::class, 'view'))->toBeTrue();
        expect(method_exists(UserPolicy::class, 'create'))->toBeTrue();
        expect(method_exists(UserPolicy::class, 'update'))->toBeTrue();
        expect(method_exists(UserPolicy::class, 'delete'))->toBeTrue();
        expect(method_exists(UserPolicy::class, 'restore'))->toBeTrue();
    });

    test('before method has correct signature', function () {
        $reflection = new ReflectionMethod(UserPolicy::class, 'before');

        expect($reflection->getParameters())->toHaveCount(2);
        expect($reflection->getParameters()[0]->getType()?->getName())->toBe(User::class);
        expect($reflection->getParameters()[1]->getType()?->getName())->toBe('string');
        expect($reflection->getReturnType()?->getName())->toBe('bool');
    });

    test('policy methods have correct signatures', function () {
        $methods = ['viewList', 'view', 'create', 'update', 'delete', 'restore'];

        foreach ($methods as $method) {
            $reflection = new ReflectionMethod(UserPolicy::class, $method);

            expect($reflection->getParameters())->toHaveCount(1);
            expect($reflection->getParameters()[0]->getType()?->getName())->toBe(User::class);
            expect($reflection->getReturnType()?->getName())->toBe('bool');
        }
    });
});

describe('UserPolicy business context', function () {
    beforeEach(function () {
        $this->policy = new UserPolicy();
    });

    test('policy supports user management operations via before hook', function () {
        // These operations aren't explicitly defined in the policy
        // but should be allowed for administrators via before hook
        $userOperations = [
            'viewProfile', 'changePassword', 'manageRoles', 'deactivate',
            'activate', 'resetPassword', 'changeRole', 'viewAuditLog',
        ];

        foreach ($userOperations as $operation) {
            expect($this->policy->before(administrator(), $operation))
                ->toBeTrue("Administrator should be able to {$operation} users");

            expect($this->policy->before(basicUser(), $operation))
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
        expect($admin->isAdministrator())->toBeTrue();
        expect($basic->isAdministrator())->toBeFalse();

        expect($this->policy->before($admin, 'any-operation'))->toBeTrue();
        expect($this->policy->before($basic, 'any-operation'))->toBeNull();
    });
});

describe('UserPolicy edge cases and security', function () {
    beforeEach(function () {
        $this->policy = new UserPolicy();
    });

    test('policy handles null user gracefully', function () {
        // Laravel typically doesn't pass null users to policies, but test defensive programming
        expect(fn () => $this->policy->before(null, 'viewList'))
            ->toThrow(TypeError::class);
    });

    test('policy methods are type-safe', function () {
        // All policy methods should require User parameter
        expect(fn () => $this->policy->viewList('not-a-user'))
            ->toThrow(TypeError::class);

        expect(fn () => $this->policy->create(123))
            ->toThrow(TypeError::class);
    });

    test('policy is consistent across multiple instances', function () {
        $policy1 = new UserPolicy();
        $policy2 = new UserPolicy();

        expect($policy1->before(administrator(), 'create'))->toBe($policy2->before(administrator(), 'create'));
        expect($policy1->viewList(basicUser()))->toBe($policy2->viewList(basicUser()));
    });

    test('policy is stateless', function () {
        // Multiple calls should return same results
        expect($this->policy->viewList(basicUser()))->toBeFalse();
        expect($this->policy->viewList(basicUser()))->toBeFalse();

        expect($this->policy->before(administrator(), 'create'))->toBeTrue();
        expect($this->policy->before(administrator(), 'create'))->toBeTrue();
    });

    test('policy correctly identifies administrator privileges', function () {
        $admin = administrator();
        $basic = basicUser();

        // Administrator should consistently pass the before hook
        $abilities = ['create', 'read', 'update', 'delete', 'custom', 'manage', 'any'];

        foreach ($abilities as $ability) {
            expect($this->policy->before($admin, $ability))->toBeTrue();
            expect($this->policy->before($basic, $ability))->toBeNull();
        }
    });
});
