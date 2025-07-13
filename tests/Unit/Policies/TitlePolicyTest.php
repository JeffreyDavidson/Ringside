<?php

declare(strict_types=1);

use App\Models\Titles\Title;
use App\Policies\TitlePolicy;
use App\Policies\WrestlerPolicy;
use Illuminate\Support\Facades\Gate;

/**
 * Unit tests for TitlePolicy authorization logic.
 *
 * UNIT TEST SCOPE:
 * - Before hook behavior for administrator bypass
 * - Individual permission method testing (viewList, view, create, update, delete, restore)
 * - Business-specific authorization methods (debut, pull, reinstate, retire, unretire, activate, deactivate)
 * - Policy method consistency and return value verification
 * - Laravel Gate integration testing
 *
 * These tests verify that the TitlePolicy correctly implements
 * the before hook pattern and authorization logic in isolation.
 * Business logic validation is handled in Actions, not policies.
 *
 * @see TitlePolicy
 */
describe('TitlePolicy Unit Tests', function () {

    beforeEach(function () {
        $this->policy = new TitlePolicy();
        $this->admin = administrator();
        $this->basicUser = basicUser();
        $this->title = Title::factory()->create();
    });

    describe('before hook behavior', function () {
        test('administrators bypass all authorization checks', function () {
            expect($this->policy->before($this->admin, 'viewList'))->toBeTrue();
            expect($this->policy->before($this->admin, 'view'))->toBeTrue();
            expect($this->policy->before($this->admin, 'create'))->toBeTrue();
            expect($this->policy->before($this->admin, 'update'))->toBeTrue();
            expect($this->policy->before($this->admin, 'delete'))->toBeTrue();
            expect($this->policy->before($this->admin, 'restore'))->toBeTrue();
        });

        test('basic users continue to individual method checks', function () {
            expect($this->policy->before($this->basicUser, 'viewList'))->toBeNull();
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

        test('before hook works for title-specific abilities', function () {
            expect($this->policy->before($this->admin, 'debut'))->toBeTrue();
            expect($this->policy->before($this->admin, 'pull'))->toBeTrue();
            expect($this->policy->before($this->admin, 'reinstate'))->toBeTrue();
            expect($this->policy->before($this->admin, 'retire'))->toBeTrue();
            expect($this->policy->before($this->admin, 'unretire'))->toBeTrue();

            expect($this->policy->before($this->basicUser, 'debut'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'pull'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'reinstate'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'retire'))->toBeNull();
            expect($this->policy->before($this->basicUser, 'unretire'))->toBeNull();
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

    describe('policy integration with Laravel Gate', function () {
        test('policy integrates correctly with Gate facade', function () {
            // Test administrator permissions through Gate
            expect(Gate::forUser($this->admin)->allows('viewList', Title::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('create', Title::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('view', Title::class))->toBeTrue();

            // Test basic user permissions through Gate
            expect(Gate::forUser($this->basicUser)->denies('viewList', Title::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('create', Title::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('view', Title::class))->toBeTrue();
        });

        test('policy works with specific title instances', function () {
            // Test with specific title instance
            expect(Gate::forUser($this->admin)->allows('view', $this->title))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('update', $this->title))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('delete', $this->title))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->denies('view', $this->title))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('update', $this->title))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('delete', $this->title))->toBeTrue();
        });

        test('policy supports title-specific operations through Gate', function () {
            // Test title activation operations (even though not explicitly defined in policy)
            expect(Gate::forUser($this->admin)->allows('debut', $this->title))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('pull', $this->title))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('reinstate', $this->title))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('retire', $this->title))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('unretire', $this->title))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->denies('debut', $this->title))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('pull', $this->title))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('reinstate', $this->title))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('retire', $this->title))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('unretire', $this->title))->toBeTrue();
        });
    });

    describe('policy method consistency', function () {
        test('all policy methods follow consistent pattern', function () {
            $methods = ['viewList', 'view', 'create', 'update', 'delete', 'restore'];

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
            ];

            foreach ($expectedMethods as $method) {
                expect(method_exists($this->policy, $method))
                    ->toBeTrue("Policy should have {$method} method");
            }
        });

        test('policy is simpler than wrestler policy', function () {
            // Title policy should have fewer methods than wrestler policy
            // since titles don't have employment, injury, or suspension management
            $titleMethods = get_class_methods($this->policy);
            $wrestlerPolicy = new WrestlerPolicy();
            $wrestlerMethods = get_class_methods($wrestlerPolicy);

            expect(count($titleMethods))->toBeLessThan(count($wrestlerMethods));

            // Title policy should not have employment-related methods
            expect(in_array('employ', $titleMethods))->toBeFalse();
            expect(in_array('release', $titleMethods))->toBeFalse();
            expect(in_array('injure', $titleMethods))->toBeFalse();
            expect(in_array('suspend', $titleMethods))->toBeFalse();
        });
    });

    describe('title-specific business context', function () {
        test('policy supports title lifecycle operations via before hook', function () {
            // These operations aren't explicitly defined in the policy
            // but should be allowed for administrators via before hook
            $titleOperations = [
                'debut', 'pull', 'reinstate', 'retire', 'unretire',
                'assignChampion', 'vacate', 'defendTitle',
            ];

            foreach ($titleOperations as $operation) {
                expect($this->policy->before($this->admin, $operation))
                    ->toBeTrue("Administrator should be able to {$operation} titles");

                expect($this->policy->before($this->basicUser, $operation))
                    ->toBeNull("Basic user should continue to individual checks for {$operation}");
            }
        });

        test('policy works with different title types', function () {
            $singlesTitle = Title::factory()->singles()->create();
            $tagTeamTitle = Title::factory()->tagTeam()->create();

            // Both title types should follow same authorization rules
            expect(Gate::forUser($this->admin)->allows('view', $singlesTitle))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('view', $tagTeamTitle))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->denies('view', $singlesTitle))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('view', $tagTeamTitle))->toBeTrue();
        });

        test('policy works with different title statuses', function () {
            $activeTitle = Title::factory()->active()->create();
            $retiredTitle = Title::factory()->retired()->create();
            $undebutedTitle = Title::factory()->create();

            // All title statuses should follow same authorization rules
            expect(Gate::forUser($this->admin)->allows('update', $activeTitle))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('update', $retiredTitle))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('update', $undebutedTitle))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->denies('update', $activeTitle))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('update', $retiredTitle))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('update', $undebutedTitle))->toBeTrue();
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
            $policy1 = new TitlePolicy();
            $policy2 = new TitlePolicy();

            expect($policy1->before($this->admin, 'create'))->toBe($policy2->before($this->admin, 'create'));
            expect($policy1->viewList($this->basicUser))->toBe($policy2->viewList($this->basicUser));
        });

        test('policy is stateless', function () {
            // Multiple calls should return same results
            expect($this->policy->viewList($this->basicUser))->toBeFalse();
            expect($this->policy->viewList($this->basicUser))->toBeFalse();

            expect($this->policy->before($this->admin, 'create'))->toBeTrue();
            expect($this->policy->before($this->admin, 'create'))->toBeTrue();
        });
    });
});
