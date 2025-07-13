<?php

declare(strict_types=1);

use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use App\Policies\WrestlerPolicy;
use Illuminate\Support\Facades\Gate;

/**
 * Unit tests for WrestlerPolicy authorization logic.
 *
 * UNIT TEST SCOPE:
 * - Before hook behavior for administrator bypass
 * - Individual permission method testing (viewList, view, create, update, delete, restore)
 * - Business-specific authorization methods (employ, release, retire, unretire, suspend, reinstate, injure, clearFromInjury)
 * - Policy method consistency and return value verification
 * - Laravel Gate integration testing
 *
 * These tests verify that the WrestlerPolicy correctly implements
 * the before hook pattern and authorization logic in isolation.
 * Business logic validation is handled in Actions, not policies.
 *
 * @see WrestlerPolicy
 */
describe('WrestlerPolicy Unit Tests', function () {

    beforeEach(function () {
        $this->policy = new WrestlerPolicy();
        $this->admin = administrator();
        $this->basicUser = basicUser();
        $this->wrestler = Wrestler::factory()->create();
    });

    describe('before hook behavior', function () {
        test('administrators bypass all authorization checks', function () {
            expect($this->policy->before($this->admin, 'viewList'))->toBeTrue();
            expect($this->policy->before($this->admin, 'view'))->toBeTrue();
            expect($this->policy->before($this->admin, 'create'))->toBeTrue();
            expect($this->policy->before($this->admin, 'update'))->toBeTrue();
            expect($this->policy->before($this->admin, 'delete'))->toBeTrue();
            expect($this->policy->before($this->admin, 'restore'))->toBeTrue();
            expect($this->policy->before($this->admin, 'employ'))->toBeTrue();
            expect($this->policy->before($this->admin, 'release'))->toBeTrue();
            expect($this->policy->before($this->admin, 'retire'))->toBeTrue();
            expect($this->policy->before($this->admin, 'unretire'))->toBeTrue();
            expect($this->policy->before($this->admin, 'suspend'))->toBeTrue();
            expect($this->policy->before($this->admin, 'reinstate'))->toBeTrue();
            expect($this->policy->before($this->admin, 'injure'))->toBeTrue();
            expect($this->policy->before($this->admin, 'clearFromInjury'))->toBeTrue();
        });

        test('basic users continue to individual method checks', function () {
            expect($this->policy->before($this->basicUser, 'viewList'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'view'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'create'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'update'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'delete'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'restore'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'employ'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'release'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'retire'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'unretire'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'suspend'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'reinstate'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'injure'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'clearFromInjury'))->toBeNull();
        });

        test('before hook works for arbitrary abilities', function () {
            expect($this->policy->before($this->admin, 'custom-ability'))->toBeTrue();
            expect($this->policy->before($this->basicUser, 'custom-ability'))->toBeNull();
        });
    });

    describe('basic CRUD permissions', function () {
        test('viewList method denies basic users', function () {
            expect($this->policy->viewList($this->basicUser))->toBeFalse();
        });

        test('view method denies basic users', function () {
            expect($this->policy->view($this->basicUser))->toBeFalse();
        });

        test('create method denies basic users', function () {
            expect($this->policy->create($this->basicUser))->toBeFalse();
        });

        test('update method denies basic users', function () {
            expect($this->policy->update($this->basicUser))->toBeFalse();
        });

        test('delete method denies basic users', function () {
            expect($this->policy->delete($this->basicUser))->toBeFalse();
        });

        test('restore method denies basic users', function () {
            expect($this->policy->restore($this->basicUser))->toBeFalse();
        });
    });

    describe('employment management permissions', function () {
        test('employ method denies basic users', function () {
            expect($this->policy->employ($this->basicUser))->toBeFalse();
        });

        test('release method denies basic users', function () {
            expect($this->policy->release($this->basicUser))->toBeFalse();
        });
    });

    describe('retirement management permissions', function () {
        test('retire method denies basic users', function () {
            expect($this->policy->retire($this->basicUser))->toBeFalse();
        });

        test('unretire method denies basic users', function () {
            expect($this->policy->unretire($this->basicUser))->toBeFalse();
        });
    });

    describe('suspension management permissions', function () {
        test('suspend method denies basic users', function () {
            expect($this->policy->suspend($this->basicUser))->toBeFalse();
        });

        test('reinstate method denies basic users', function () {
            expect($this->policy->reinstate($this->basicUser))->toBeFalse();
        });
    });

    describe('injury management permissions', function () {
        test('injure method denies basic users', function () {
            expect($this->policy->injure($this->basicUser))->toBeFalse();
        });

        test('clearFromInjury method denies basic users', function () {
            expect($this->policy->clearFromInjury($this->basicUser))->toBeFalse();
        });
    });

    describe('policy integration with Laravel Gate', function () {
        test('policy integrates correctly with Gate facade', function () {
            // Test administrator permissions through Gate
            expect(Gate::forUser($this->admin)->allows('viewList', Wrestler::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('create', Wrestler::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('employ', Wrestler::class))->toBeTrue();

            // Test basic user permissions through Gate
            expect(Gate::forUser($this->basicUser)->denies('viewList', Wrestler::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('create', Wrestler::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('employ', Wrestler::class))->toBeTrue();
        });

        test('policy works with specific wrestler instances', function () {
            // Test with specific wrestler instance
            expect(Gate::forUser($this->admin)->allows('view', $this->wrestler))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('update', $this->wrestler))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('delete', $this->wrestler))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->denies('view', $this->wrestler))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('update', $this->wrestler))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('delete', $this->wrestler))->toBeTrue();
        });
    });

    describe('policy method consistency', function () {
        test('all policy methods follow consistent pattern', function () {
            $methods = [
                'viewList', 'view', 'create', 'update', 'delete', 'restore',
                'employ', 'release', 'retire', 'unretire', 'suspend',
                'reinstate', 'injure', 'clearFromInjury',
            ];

            foreach ($methods as $method) {
                // All methods should return false for basic users
                expect($this->policy->{$method}($this->basicUser))
                    ->toBeFalse("Method {$method} should deny basic users");

                // All methods should be bypassed for administrators via before hook
                expect($this->policy->before($this->admin, $method))
                    ->toBeTrue("Method {$method} should be bypassed for administrators");
            }
        });

        test('policy has all expected methods', function () {
            $expectedMethods = [
                'before', 'viewList', 'view', 'create', 'update', 'delete', 'restore',
                'employ', 'release', 'retire', 'unretire', 'suspend',
                'reinstate', 'injure', 'clearFromInjury',
            ];

            foreach ($expectedMethods as $method) {
                expect(method_exists($this->policy, $method))
                    ->toBeTrue("Policy should have {$method} method");
            }
        });
    });

    describe('edge cases and security', function () {
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
            $policy1 = new WrestlerPolicy();
            $policy2 = new WrestlerPolicy();

            expect($policy1->before($this->admin, 'create'))->toBe($policy2->before($this->admin, 'create'));
            expect($policy1->viewList($this->basicUser))->toBe($policy2->viewList($this->basicUser));
        });
    });
});
