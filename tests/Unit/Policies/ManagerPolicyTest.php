<?php

declare(strict_types=1);

use App\Actions\Managers\InjureAction;
use App\Models\Roster\Managers\Manager;
use App\Policies\ManagerPolicy;
use App\Policies\WrestlerPolicy;
use Illuminate\Support\Facades\Gate;

/**
 * Unit tests for ManagerPolicy authorization logic.
 *
 * UNIT TEST SCOPE:
 * - global Gate hook behavior for administrator bypass
 * - Individual permission method testing (viewAny, view, create, update, delete, restore)
 * - Business-specific authorization methods (employ, release, retire, unretire, suspend, reinstate, injure, clearFromInjury)
 * - Policy method consistency and return value verification
 * - Laravel Gate integration testing
 *
 * These tests verify that the ManagerPolicy correctly implements
 * the global Gate hook and authorization logic in isolation.
 * Business logic validation is handled in Actions, not policies.
 *
 * @see ManagerPolicy
 */
describe('ManagerPolicy Unit Tests', function () {

    beforeEach(function () {
        $this->policy = new ManagerPolicy();
        $this->admin = administrator();
        $this->basicUser = basicUser();
        $this->manager = Manager::factory()->create();
    });

    describe('global Gate hook behavior', function () {
        test('administrators bypass all authorization checks', function () {
            expect(Gate::forUser($this->admin)->raw('viewAny'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('view'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('create'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('update'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('delete'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('restore'))->toBeTrue();
        });

        test('basic users continue to individual method checks', function () {
            expect(Gate::forUser($this->basicUser)->raw('viewAny'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('view'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('create'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('update'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('delete'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('restore'))->toBeNull();
        });

        test('global Gate hook works for arbitrary abilities', function () {
            expect(Gate::forUser($this->admin)->raw('custom-ability'))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->raw('custom-ability'))->toBeNull();
        });

        test('global Gate hook works for manager-specific abilities', function () {
            expect(Gate::forUser($this->admin)->raw('employ'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('release'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('retire'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('unretire'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('injure'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('clearFromInjury'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('suspend'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('reinstate'))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->raw('employ'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('release'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('retire'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('unretire'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('injure'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('clearFromInjury'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('suspend'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('reinstate'))->toBeNull();
        });
    });

    describe('basic CRUD permissions', function () {
        test('viewAny method denies basic users', function () {
            expect($this->policy->viewAny($this->basicUser))->toBeFalse();
        });

        test('view method denies basic users', function () {
            expect($this->policy->view($this->basicUser, $this->manager))->toBeFalse();
        });

        test('create method denies basic users', function () {
            expect($this->policy->create($this->basicUser))->toBeFalse();
        });

        test('update method denies basic users', function () {
            expect($this->policy->update($this->basicUser, $this->manager))->toBeFalse();
        });

        test('delete method denies basic users', function () {
            expect($this->policy->delete($this->basicUser, $this->manager))->toBeFalse();
        });

        test('restore method denies basic users', function () {
            expect($this->policy->restore($this->basicUser, $this->manager))->toBeFalse();
        });
    });

    describe('policy integration with Laravel Gate', function () {
        test('policy integrates correctly with Gate facade', function () {
            // Test administrator permissions through Gate
            expect(Gate::forUser($this->admin)->allows('viewAny', Manager::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('create', Manager::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('view', $this->manager))->toBeTrue();

            // Test basic user permissions through Gate
            expect(Gate::forUser($this->basicUser)->denies('viewAny', Manager::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('create', Manager::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('view', $this->manager))->toBeTrue();
        });

        test('policy works with specific manager instances', function () {
            // Test with specific manager instance
            expect(Gate::forUser($this->admin)->allows('view', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('update', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('delete', $this->manager))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->denies('view', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('update', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('delete', $this->manager))->toBeTrue();
        });

        test('policy supports manager-specific operations through Gate', function () {
            // Test manager employment operations
            expect(Gate::forUser($this->admin)->allows('employ', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('release', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('retire', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('unretire', $this->manager))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->denies('employ', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('release', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('retire', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('unretire', $this->manager))->toBeTrue();

            // Test manager injury operations
            expect(Gate::forUser($this->admin)->allows('injure', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('clearFromInjury', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('suspend', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('reinstate', $this->manager))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->denies('injure', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('clearFromInjury', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('suspend', $this->manager))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('reinstate', $this->manager))->toBeTrue();
        });
    });

    describe('policy method consistency', function () {
        test('all policy methods follow consistent pattern', function () {
            $methods = ['viewAny', 'view', 'create', 'update', 'delete', 'restore'];

            foreach ($methods as $method) {
                $subject = in_array($method, ['viewAny', 'create'], true) ? Manager::class : $this->manager;

                expect(Gate::forUser($this->basicUser)->denies($method, $subject))
                    ->toBeTrue("Method {$method} should deny basic users");

                // All methods should be bypassed for administrators via the global Gate hook
                expect(Gate::forUser($this->admin)->raw($method))
                    ->toBeTrue("Method {$method} should be bypassed for administrators");
            }
        });

        test('policy has all expected methods', function () {
            $expectedMethods = [
                'viewAny', 'view', 'create', 'update', 'delete', 'restore',
            ];

            foreach ($expectedMethods as $method) {
                expect(method_exists($this->policy, $method))
                    ->toBeTrue("Policy should have {$method} method");
            }
        });

        test('policy is similar to wrestler policy but simpler', function () {
            // Manager policy should have similar methods to wrestler policy
            // since they're both individual roster members
            $managerMethods = get_class_methods($this->policy);
            $wrestlerPolicy = new WrestlerPolicy();
            $wrestlerMethods = get_class_methods($wrestlerPolicy);

            // Should have the same basic structure
            expect($managerMethods)->not->toContain('before');
            expect(in_array('viewAny', $managerMethods))->toBeTrue();
            expect(in_array('create', $managerMethods))->toBeTrue();
            expect(in_array('update', $managerMethods))->toBeTrue();
            expect(in_array('delete', $managerMethods))->toBeTrue();
            expect(in_array('restore', $managerMethods))->toBeTrue();
        });
    });

    describe('manager-specific business context', function () {
        test('policy supports manager lifecycle operations via the global Gate hook', function () {
            // These operations aren't explicitly defined in the policy
            // but should be allowed for administrators via the global Gate hook
            $managerOperations = [
                'employ', 'release', 'retire', 'unretire',
                'injure', 'clearFromInjury', 'suspend', 'reinstate',
                'assignToWrestler', 'assignToTagTeam', 'removeFromAssignment',
            ];

            foreach ($managerOperations as $operation) {
                expect(Gate::forUser($this->admin)->raw($operation))
                    ->toBeTrue("Administrator should be able to {$operation} managers");

                expect(Gate::forUser($this->basicUser)->raw($operation))
                    ->toBeNull("Basic user should continue to individual checks for {$operation}");
            }
        });

        test('policy works with different manager statuses', function () {
            $employedManager = Manager::factory()->employed()->create();
            $injuredManager = Manager::factory()->injured()->create();
            $retiredManager = Manager::factory()->retired()->create();
            $suspendedManager = Manager::factory()->suspended()->create();

            // All manager statuses should follow same authorization rules
            foreach ([$employedManager, $injuredManager, $retiredManager, $suspendedManager] as $manager) {
                expect(Gate::forUser($this->admin)->allows('view', $manager))->toBeTrue();
                expect(Gate::forUser($this->basicUser)->denies('view', $manager))->toBeTrue();
            }
        });

        // TODO: Add management assignment policy methods when business requirements are clarified
        // test('policy handles manager management assignment context', function () {
        //     $manager = Manager::factory()->bookable()->create();
        //
        //     // Management assignment operations should follow same authorization pattern
        //     $managementOperations = [
        //         'assignToWrestler', 'removeFromWrestler',
        //         'assignToTagTeam', 'removeFromTagTeam',
        //         'viewManagedEntities', 'manageManagedEntities'
        //     ];
        //
        //     foreach ($managementOperations as $operation) {
        //         expect(Gate::forUser($this->admin)->allows($operation, $manager))->toBeTrue();
        //         expect(Gate::forUser($this->basicUser)->denies($operation, $manager))->toBeTrue();
        //     }
        // });
    });

    describe('edge cases and security', function () {
        test('policy is consistent across multiple instances', function () {
            $policy1 = new ManagerPolicy();
            $policy2 = new ManagerPolicy();

            expect($policy1->viewAny($this->basicUser))->toBe($policy2->viewAny($this->basicUser));
        });

        test('policy is stateless', function () {
            // Multiple calls should return same results
            expect($this->policy->viewAny($this->basicUser))->toBeFalse();
            expect($this->policy->viewAny($this->basicUser))->toBeFalse();

            expect(Gate::forUser($this->admin)->raw('create'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('create'))->toBeTrue();
        });

        test('policy handles complex manager states consistently', function () {
            // Create manager with multiple statuses (avoid conflicting business rules)
            $complexManager = Manager::factory()->employed()->create();

            // Apply business-compatible status changes
            resolve(InjureAction::class)->handle($complexManager, now());
            // Note: Cannot suspend an injured manager per business rules
            // resolve(SuspendAction::class)->handle($complexManager, now());

            // Authorization should remain consistent regardless of complex state
            expect(Gate::forUser($this->admin)->allows('view', $complexManager))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('update', $complexManager))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->denies('view', $complexManager))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('update', $complexManager))->toBeTrue();
        });
    });
});
