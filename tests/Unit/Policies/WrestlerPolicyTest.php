<?php

declare(strict_types=1);

use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use App\Policies\WrestlerPolicy;
use Illuminate\Support\Facades\Gate;

/**
 * Unit tests for WrestlerPolicy authorization logic.
 *
 * UNIT TEST SCOPE:
 * - global Gate hook behavior for administrator bypass
 * - Individual permission method testing (viewAny, view, create, update, delete, restore)
 * - Business-specific authorization methods (employ, release, retire, unretire, suspend, reinstate, injure, clearFromInjury)
 * - Policy method consistency and return value verification
 * - Laravel Gate integration testing
 *
 * These tests verify that the WrestlerPolicy correctly implements
 * the global Gate hook and authorization logic in isolation.
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

    describe('global Gate hook behavior', function () {
        test('administrators bypass all authorization checks', function () {
            expect(Gate::forUser($this->admin)->raw('viewAny'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('view'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('create'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('update'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('delete'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('restore'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('employ'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('release'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('retire'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('unretire'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('suspend'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('reinstate'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('injure'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('clearFromInjury'))->toBeTrue();
        });

        test('basic users continue to individual method checks', function () {
            expect(Gate::forUser($this->basicUser)->raw('viewAny'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('view'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('create'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('update'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('delete'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('restore'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('employ'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('release'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('retire'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('unretire'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('suspend'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('reinstate'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('injure'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('clearFromInjury'))->toBeNull();
        });

        test('global Gate hook works for arbitrary abilities', function () {
            expect(Gate::forUser($this->admin)->raw('custom-ability'))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->raw('custom-ability'))->toBeNull();
        });
    });

    describe('basic CRUD permissions', function () {
        test('viewAny method denies basic users', function () {
            expect($this->policy->viewAny($this->basicUser))->toBeFalse();
        });

        test('view method denies basic users', function () {
            expect($this->policy->view($this->basicUser, $this->wrestler))->toBeFalse();
        });

        test('create method denies basic users', function () {
            expect($this->policy->create($this->basicUser))->toBeFalse();
        });

        test('update method denies basic users', function () {
            expect($this->policy->update($this->basicUser, $this->wrestler))->toBeFalse();
        });

        test('delete method denies basic users', function () {
            expect($this->policy->delete($this->basicUser, $this->wrestler))->toBeFalse();
        });

        test('restore method denies basic users', function () {
            expect($this->policy->restore($this->basicUser, $this->wrestler))->toBeFalse();
        });
    });

    describe('employment management permissions', function () {
        test('employ method denies basic users', function () {
            expect($this->policy->employ($this->basicUser, $this->wrestler))->toBeFalse();
        });

        test('release method denies basic users', function () {
            expect($this->policy->release($this->basicUser, $this->wrestler))->toBeFalse();
        });
    });

    describe('retirement management permissions', function () {
        test('retire method denies basic users', function () {
            expect($this->policy->retire($this->basicUser, $this->wrestler))->toBeFalse();
        });

        test('unretire method denies basic users', function () {
            expect($this->policy->unretire($this->basicUser, $this->wrestler))->toBeFalse();
        });
    });

    describe('suspension management permissions', function () {
        test('suspend method denies basic users', function () {
            expect($this->policy->suspend($this->basicUser, $this->wrestler))->toBeFalse();
        });

        test('reinstate method denies basic users', function () {
            expect($this->policy->reinstate($this->basicUser, $this->wrestler))->toBeFalse();
        });
    });

    describe('injury management permissions', function () {
        test('injure method denies basic users', function () {
            expect($this->policy->injure($this->basicUser, $this->wrestler))->toBeFalse();
        });

        test('clearFromInjury method denies basic users', function () {
            expect($this->policy->clearFromInjury($this->basicUser, $this->wrestler))->toBeFalse();
        });
    });

    describe('policy integration with Laravel Gate', function () {
        test('policy integrates correctly with Gate facade', function () {
            // Test administrator permissions through Gate
            expect(Gate::forUser($this->admin)->allows('viewAny', Wrestler::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('create', Wrestler::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('employ', $this->wrestler))->toBeTrue();

            // Test basic user permissions through Gate
            expect(Gate::forUser($this->basicUser)->denies('viewAny', Wrestler::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('create', Wrestler::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('employ', $this->wrestler))->toBeTrue();
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
                'viewAny', 'view', 'create', 'update', 'delete', 'restore',
                'employ', 'release', 'retire', 'unretire', 'suspend',
                'reinstate', 'injure', 'clearFromInjury',
            ];

            foreach ($methods as $method) {
                $subject = in_array($method, ['viewAny', 'create'], true) ? Wrestler::class : $this->wrestler;

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
        test('policy is consistent across multiple instances', function () {
            $policy1 = new WrestlerPolicy();
            $policy2 = new WrestlerPolicy();

            expect($policy1->viewAny($this->basicUser))->toBe($policy2->viewAny($this->basicUser));
        });
    });
});
