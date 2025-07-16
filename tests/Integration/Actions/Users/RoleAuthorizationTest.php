<?php

declare(strict_types=1);

use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Models\Managers\Manager;
use App\Models\Titles\Title;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;

/**
 * Integration tests for User Role and Permission system.
 *
 * INTEGRATION TEST SCOPE:
 * - Role-based authorization integration
 * - Permission system workflows
 * - User status and role interaction
 * - Gate integration with policies
 * - Cross-component role validation
 */
describe('User Role Integration Tests', function () {

    beforeEach(function () {
        $this->administrator = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->create(['role' => Role::Basic]);
        $this->unverifiedUser = User::factory()->unverified()->create();
    });

    describe('role-based authorization integration', function () {
        test('administrator role integrates with Gate system', function () {
            actingAs($this->administrator);

            // Administrator should pass all Gate checks across different models
            expect(Gate::allows('viewList', User::class))->toBeTrue();
            expect(Gate::allows('create', User::class))->toBeTrue();
            expect(Gate::allows('update', User::class))->toBeTrue();
            expect(Gate::allows('delete', User::class))->toBeTrue();
            expect(Gate::allows('restore', User::class))->toBeTrue();

            // Administrator should also pass custom abilities
            expect(Gate::allows('manageUsers', User::class))->toBeTrue();
            expect(Gate::allows('changeUserRoles', User::class))->toBeTrue();
            expect(Gate::allows('viewAuditLogs', User::class))->toBeTrue();
        });

        test('basic user role integrates with Gate system', function () {
            actingAs($this->basicUser);

            // Basic user should be denied access across different operations
            expect(Gate::denies('viewList', User::class))->toBeTrue();
            expect(Gate::denies('create', User::class))->toBeTrue();
            expect(Gate::denies('update', User::class))->toBeTrue();
            expect(Gate::denies('delete', User::class))->toBeTrue();
            expect(Gate::denies('restore', User::class))->toBeTrue();

            // Basic user should also be denied custom abilities
            expect(Gate::denies('manageUsers', User::class))->toBeTrue();
            expect(Gate::denies('changeUserRoles', User::class))->toBeTrue();
            expect(Gate::denies('viewAuditLogs', User::class))->toBeTrue();
        });

        test('role system works consistently across user instances', function () {
            $user1 = User::factory()->administrator()->create();
            $user2 = User::factory()->administrator()->create();
            $user3 = User::factory()->create(['role' => Role::Basic]);
            $user4 = User::factory()->create(['role' => Role::Basic]);

            // All administrators should have same permissions
            expect($user1->isAdministrator())->toBeTrue();
            expect($user2->isAdministrator())->toBeTrue();
            expect(Gate::forUser($user1)->allows('create', User::class))->toBeTrue();
            expect(Gate::forUser($user2)->allows('create', User::class))->toBeTrue();

            // All basic users should have same restrictions
            expect($user3->isAdministrator())->toBeFalse();
            expect($user4->isAdministrator())->toBeFalse();
            expect(Gate::forUser($user3)->denies('create', User::class))->toBeTrue();
            expect(Gate::forUser($user4)->denies('create', User::class))->toBeTrue();
        });
    });

    describe('user status and role interaction', function () {
        test('user status does not affect role-based permissions', function () {
            $activeAdmin = User::factory()->administrator()->create(['status' => UserStatus::Active]);
            $inactiveAdmin = User::factory()->administrator()->create(['status' => UserStatus::Inactive]);
            $unverifiedAdmin = User::factory()->administrator()->create(['status' => UserStatus::Unverified]);

            // All administrators should have same permissions regardless of status
            expect(Gate::forUser($activeAdmin)->allows('create', User::class))->toBeTrue();
            expect(Gate::forUser($inactiveAdmin)->allows('create', User::class))->toBeTrue();
            expect(Gate::forUser($unverifiedAdmin)->allows('create', User::class))->toBeTrue();

            $activeBasic = User::factory()->create(['role' => Role::Basic, 'status' => UserStatus::Active]);
            $inactiveBasic = User::factory()->create(['role' => Role::Basic, 'status' => UserStatus::Inactive]);
            $unverifiedBasic = User::factory()->create(['role' => Role::Basic, 'status' => UserStatus::Unverified]);

            // All basic users should be denied regardless of status
            expect(Gate::forUser($activeBasic)->denies('create', User::class))->toBeTrue();
            expect(Gate::forUser($inactiveBasic)->denies('create', User::class))->toBeTrue();
            expect(Gate::forUser($unverifiedBasic)->denies('create', User::class))->toBeTrue();
        });

        test('role changes are reflected immediately in authorization', function () {
            $user = User::factory()->create(['role' => Role::Basic]);

            // Initially basic user should be denied
            expect($user->isAdministrator())->toBeFalse();
            expect(Gate::forUser($user)->denies('create', User::class))->toBeTrue();

            // Change role to administrator
            $user->update(['role' => Role::Administrator]);
            $user->refresh();

            // Should now have administrator permissions
            expect($user->isAdministrator())->toBeTrue();
            expect(Gate::forUser($user)->allows('create', User::class))->toBeTrue();
        });

        test('status changes do not affect role-based authorization', function () {
            $admin = User::factory()->administrator()->create(['status' => UserStatus::Active]);

            // Initially should have permissions
            expect(Gate::forUser($admin)->allows('create', User::class))->toBeTrue();

            // Change status
            $admin->update(['status' => UserStatus::Inactive]);
            $admin->refresh();

            // Should still have administrator permissions
            expect($admin->isAdministrator())->toBeTrue();
            expect(Gate::forUser($admin)->allows('create', User::class))->toBeTrue();
        });
    });

    describe('cross-component role validation', function () {
        test('user role system integrates with other entity policies', function () {
            actingAs($this->administrator);

            // Administrator should have access to other entity management
            expect(Gate::allows('viewList', Wrestler::class))->toBeTrue();
            expect(Gate::allows('viewList', Manager::class))->toBeTrue();
            expect(Gate::allows('viewList', Title::class))->toBeTrue();

            actingAs($this->basicUser);

            // Basic user should be denied access to other entities
            expect(Gate::denies('viewList', Wrestler::class))->toBeTrue();
            expect(Gate::denies('viewList', Manager::class))->toBeTrue();
            expect(Gate::denies('viewList', Title::class))->toBeTrue();
        });

        test('authentication system respects user roles', function () {
            // Test authentication state integration with roles
            actingAs($this->administrator);
            expect(auth()->check())->toBeTrue();
            expect(auth()->user()->isAdministrator())->toBeTrue();

            actingAs($this->basicUser);
            expect(auth()->check())->toBeTrue();
            expect(auth()->user()->isAdministrator())->toBeFalse();
        });
    });

    describe('role management workflows', function () {
        test('role promotion workflow maintains consistency', function () {
            $user = User::factory()->create(['role' => Role::Basic]);

            // Verify initial state
            expect($user->role)->toBe(Role::Basic);
            expect($user->isAdministrator())->toBeFalse();
            expect(Gate::forUser($user)->denies('create', User::class))->toBeTrue();

            // Promote to administrator
            $user->update(['role' => Role::Administrator]);
            $user->refresh();

            // Verify promotion worked across all systems
            expect($user->role)->toBe(Role::Administrator);
            expect($user->isAdministrator())->toBeTrue();
            expect(Gate::forUser($user)->allows('create', User::class))->toBeTrue();

            // Verify in database
            $userFromDb = User::find($user->id);
            expect($userFromDb->role)->toBe(Role::Administrator);
            expect($userFromDb->isAdministrator())->toBeTrue();
        });

        test('role demotion workflow maintains consistency', function () {
            $user = User::factory()->administrator()->create();

            // Verify initial administrator state
            expect($user->role)->toBe(Role::Administrator);
            expect($user->isAdministrator())->toBeTrue();
            expect(Gate::forUser($user)->allows('create', User::class))->toBeTrue();

            // Demote to basic user
            $user->update(['role' => Role::Basic]);
            $user->refresh();

            // Verify demotion worked across all systems
            expect($user->role)->toBe(Role::Basic);
            expect($user->isAdministrator())->toBeFalse();
            expect(Gate::forUser($user)->denies('create', User::class))->toBeTrue();

            // Verify in database
            $userFromDb = User::find($user->id);
            expect($userFromDb->role)->toBe(Role::Basic);
            expect($userFromDb->isAdministrator())->toBeFalse();
        });

        test('bulk role operations maintain system integrity', function () {
            $users = User::factory()->count(5)->create(['role' => Role::Basic]);

            // Verify all are basic users initially
            foreach ($users as $user) {
                expect($user->isAdministrator())->toBeFalse();
            }

            // Bulk promote to administrators
            User::whereIn('id', $users->pluck('id'))->update(['role' => Role::Administrator]);

            // Verify all are now administrators
            $updatedUsers = User::whereIn('id', $users->pluck('id'))->get();
            foreach ($updatedUsers as $user) {
                expect($user->isAdministrator())->toBeTrue();
                expect(Gate::forUser($user)->allows('create', User::class))->toBeTrue();
            }
        });
    });

    describe('security and edge cases', function () {
        test('role system prevents privilege escalation', function () {
            $basicUser = User::factory()->create(['role' => Role::Basic]);

            actingAs($basicUser);

            // Basic user should not be able to change their own role
            expect(Gate::denies('update', $basicUser))->toBeTrue();

            // Even if they somehow attempt to update their role directly,
            // the policy system should still deny them access
            $basicUser->role = Role::Administrator;
            expect(Gate::denies('create', User::class))->toBeTrue();
        });

        test('role enum validation prevents invalid roles', function () {
            $user = User::factory()->create();

            // Valid role assignments should work
            $user->role = Role::Administrator;
            expect($user->role)->toBe(Role::Administrator);

            $user->role = Role::Basic;
            expect($user->role)->toBe(Role::Basic);

            // Invalid role values should be rejected by enum type system
            expect(function () use ($user) {
                $user->role = 'invalid-role';
            })->toThrow(TypeError::class);
        });

        test('role system handles concurrent access correctly', function () {
            $admin1 = User::factory()->administrator()->create();
            $admin2 = User::factory()->administrator()->create();

            // Multiple administrators should be able to operate simultaneously
            expect(Gate::forUser($admin1)->allows('create', User::class))->toBeTrue();
            expect(Gate::forUser($admin2)->allows('create', User::class))->toBeTrue();

            // Their permissions should not interfere with each other
            actingAs($admin1);
            expect(Gate::allows('create', User::class))->toBeTrue();

            actingAs($admin2);
            expect(Gate::allows('create', User::class))->toBeTrue();
        });

        test('role system maintains consistency after user deletion and restoration', function () {
            $admin = User::factory()->administrator()->create();

            // Verify initial state
            expect($admin->isAdministrator())->toBeTrue();

            // Soft delete user
            $admin->delete();

            // Role should still be maintained on deleted user
            expect($admin->fresh()->isAdministrator())->toBeTrue();

            // Restore user
            $admin->restore();

            // Role should still work after restoration
            expect($admin->fresh()->isAdministrator())->toBeTrue();
            expect(Gate::forUser($admin->fresh())->allows('create', User::class))->toBeTrue();
        });
    });
});
