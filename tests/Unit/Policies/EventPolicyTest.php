<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Users\User;
use App\Policies\EventPolicy;
use Illuminate\Support\Facades\Gate;

/**
 * Unit tests for EventPolicy authorization logic.
 *
 * These tests focus on the authorization logic in isolation,
 * testing each permission method independently.
 *
 * @see EventPolicy
 */
describe('EventPolicy Unit Tests', function () {

    beforeEach(function () {
        $this->policy = new EventPolicy();
        $this->admin = administrator();
        $this->basicUser = basicUser();
        $this->event = Event::factory()->create();
    });

    describe('before hook behavior', function () {
        test('administrators bypass all authorization checks', function () {
            expect($this->policy->before($this->admin, 'viewAny'))->toBeTrue();
            expect($this->policy->before($this->admin, 'view'))->toBeTrue();
            expect($this->policy->before($this->admin, 'create'))->toBeTrue();
            expect($this->policy->before($this->admin, 'update'))->toBeTrue();
            expect($this->policy->before($this->admin, 'delete'))->toBeTrue();
            expect($this->policy->before($this->admin, 'restore'))->toBeTrue();
        });

        test('basic users continue to individual method checks', function () {
            expect($this->policy->before($this->basicUser, 'viewAny'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'view'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'create'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'update'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'delete'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'restore'))->toBeNull();
        });

        test('before hook works for arbitrary abilities', function () {
            expect($this->policy->before($this->admin, 'custom-ability'))->toBeTrue();
            expect($this->policy->before($this->basicUser, 'custom-ability'))->toBeNull();
        });
    });

    describe('basic CRUD permissions', function () {
        test('viewAny method denies basic users', function () {
            expect($this->policy->viewAny($this->basicUser))->toBeFalse();
        });

        test('view method denies basic users', function () {
            expect($this->policy->view($this->basicUser, $this->event))->toBeFalse();
        });

        test('create method denies basic users', function () {
            expect($this->policy->create($this->basicUser))->toBeFalse();
        });

        test('update method denies basic users', function () {
            expect($this->policy->update($this->basicUser, $this->event))->toBeFalse();
        });

        test('delete method denies basic users', function () {
            expect($this->policy->delete($this->basicUser, $this->event))->toBeFalse();
        });

        test('restore method denies basic users', function () {
            expect($this->policy->restore($this->basicUser, $this->event))->toBeFalse();
        });
    });

    describe('policy integration with Laravel Gate', function () {
        test('policy integrates correctly with Gate facade', function () {
            // Test administrator permissions through Gate
            expect(Gate::forUser($this->admin)->allows('viewAny', Event::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('create', Event::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('update', $this->event))->toBeTrue();

            // Test basic user permissions through Gate
            expect(Gate::forUser($this->basicUser)->denies('viewAny', Event::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('create', Event::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('update', $this->event))->toBeTrue();
        });

        test('policy works with specific event instances', function () {
            // Test with specific event instance
            expect(Gate::forUser($this->admin)->allows('view', $this->event))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('update', $this->event))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('delete', $this->event))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->denies('view', $this->event))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('update', $this->event))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('delete', $this->event))->toBeTrue();
        });
    });

    describe('policy method consistency', function () {
        test('all policy methods follow consistent pattern', function () {
            $methods = [
                'viewAny', 'view', 'create', 'update', 'delete', 'restore',
            ];

            foreach ($methods as $method) {
                $subject = in_array($method, ['viewAny', 'create'], true) ? Event::class : $this->event;

                expect(Gate::forUser($this->basicUser)->denies($method, $subject))
                    ->toBeTrue("Method {$method} should deny basic users");

                // All methods should be bypassed for administrators via before hook
                expect($this->policy->before($this->admin, $method))
                    ->toBeTrue("Method {$method} should be bypassed for administrators");
            }
        });

        test('policy has all expected methods', function () {
            $expectedMethods = [
                'before', 'viewAny', 'view', 'create', 'update', 'delete', 'restore',
            ];

            foreach ($expectedMethods as $method) {
                expect(method_exists($this->policy, $method))
                    ->toBeTrue("Policy should have {$method} method");
            }
        });
    });

    describe('edge cases and security', function () {
        test('policy is consistent across multiple instances', function () {
            $policy1 = new EventPolicy();
            $policy2 = new EventPolicy();

            expect($policy1->before($this->admin, 'create'))->toBe($policy2->before($this->admin, 'create'));
            expect($policy1->viewAny($this->basicUser))->toBe($policy2->viewAny($this->basicUser));
        });
    });
});
