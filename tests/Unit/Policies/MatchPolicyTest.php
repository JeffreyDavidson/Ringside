<?php

declare(strict_types=1);

use App\Models\Matches\EventMatch;
use App\Models\Users\User;
use App\Policies\MatchPolicy;
use Illuminate\Support\Facades\Gate;

/**
 * Unit tests for MatchPolicy authorization logic.
 *
 * These tests focus on the authorization logic in isolation,
 * testing each permission method independently.
 *
 * @see MatchPolicy
 */
describe('MatchPolicy Unit Tests', function () {

    beforeEach(function () {
        $this->policy = new MatchPolicy();
        $this->admin = administrator();
        $this->basicUser = basicUser();
        $this->eventMatch = EventMatch::factory()->create();
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
    });

    describe('basic CRUD permissions', function () {
        test('viewAny method denies basic users', function () {
            expect($this->policy->viewAny($this->basicUser))->toBeFalse();
        });

        test('view method denies basic users', function () {
            expect($this->policy->view($this->basicUser, $this->eventMatch))->toBeFalse();
        });

        test('create method denies basic users', function () {
            expect($this->policy->create($this->basicUser))->toBeFalse();
        });

        test('update method denies basic users', function () {
            expect($this->policy->update($this->basicUser, $this->eventMatch))->toBeFalse();
        });

        test('delete method denies basic users', function () {
            expect($this->policy->delete($this->basicUser, $this->eventMatch))->toBeFalse();
        });

        test('restore method denies basic users', function () {
            expect($this->policy->restore($this->basicUser, $this->eventMatch))->toBeFalse();
        });
    });

    describe('policy integration with Laravel Gate', function () {
        test('policy integrates correctly with Gate facade', function () {
            // Test administrator permissions through Gate
            expect(Gate::forUser($this->admin)->allows('viewAny', EventMatch::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('create', EventMatch::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('update', $this->eventMatch))->toBeTrue();

            // Test basic user permissions through Gate
            expect(Gate::forUser($this->basicUser)->denies('viewAny', EventMatch::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('create', EventMatch::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('update', $this->eventMatch))->toBeTrue();
        });

        test('policy works with specific event match instances', function () {
            // Test with specific event match instance
            expect(Gate::forUser($this->admin)->allows('view', $this->eventMatch))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('update', $this->eventMatch))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('delete', $this->eventMatch))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->denies('view', $this->eventMatch))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('update', $this->eventMatch))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('delete', $this->eventMatch))->toBeTrue();
        });
    });

    describe('policy method consistency', function () {
        test('all policy methods follow consistent pattern', function () {
            $methods = [
                'viewAny', 'view', 'create', 'update', 'delete', 'restore',
            ];

            foreach ($methods as $method) {
                $subject = in_array($method, ['viewAny', 'create'], true) ? EventMatch::class : $this->eventMatch;

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
    });

    describe('edge cases and security', function () {
        test('policy is consistent across multiple instances', function () {
            $policy1 = new MatchPolicy();
            $policy2 = new MatchPolicy();

            expect($policy1->viewAny($this->basicUser))->toBe($policy2->viewAny($this->basicUser));
        });
    });
});
