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
 * - global Gate hook behavior for administrator bypass
 * - Individual permission method testing (viewAny, view, create, update, delete, restore)
 * - Business-specific authorization methods (debut, pull, reinstate, retire, unretire, activate, deactivate)
 * - Policy method consistency and return value verification
 * - Laravel Gate integration testing
 *
 * These tests verify that the TitlePolicy correctly implements
 * the global Gate hook and authorization logic in isolation.
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

        test('global Gate hook works for title-specific abilities', function () {
            expect(Gate::forUser($this->admin)->raw('debut'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('pull'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('reinstate'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('retire'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('unretire'))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->raw('debut'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('pull'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('reinstate'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('retire'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('unretire'))->toBeNull();
        });
    });

    describe('basic CRUD permissions', function () {
        test('viewAny method denies basic users', function () {
            expect($this->policy->viewAny($this->basicUser))->toBeFalse();
        });

        test('view method denies basic users', function () {
            expect($this->policy->view($this->basicUser, $this->title))->toBeFalse();
        });

        test('create method denies basic users', function () {
            expect($this->policy->create($this->basicUser))->toBeFalse();
        });

        test('update method denies basic users', function () {
            expect($this->policy->update($this->basicUser, $this->title))->toBeFalse();
        });

        test('delete method denies basic users', function () {
            expect($this->policy->delete($this->basicUser, $this->title))->toBeFalse();
        });

        test('restore method denies basic users', function () {
            expect($this->policy->restore($this->basicUser, $this->title))->toBeFalse();
        });
    });

    describe('policy integration with Laravel Gate', function () {
        test('policy integrates correctly with Gate facade', function () {
            // Test administrator permissions through Gate
            expect(Gate::forUser($this->admin)->allows('viewAny', Title::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('create', Title::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('view', $this->title))->toBeTrue();

            // Test basic user permissions through Gate
            expect(Gate::forUser($this->basicUser)->denies('viewAny', Title::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('create', Title::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('view', $this->title))->toBeTrue();
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
            $methods = ['viewAny', 'view', 'create', 'update', 'delete', 'restore'];

            foreach ($methods as $method) {
                $subject = in_array($method, ['viewAny', 'create'], true) ? Title::class : $this->title;

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
        test('policy supports title lifecycle operations via the global Gate hook', function () {
            // These operations aren't explicitly defined in the policy
            // but should be allowed for administrators via the global Gate hook
            $titleOperations = [
                'debut', 'pull', 'reinstate', 'retire', 'unretire',
                'assignChampion', 'vacate', 'defendTitle',
            ];

            foreach ($titleOperations as $operation) {
                expect(Gate::forUser($this->admin)->raw($operation))
                    ->toBeTrue("Administrator should be able to {$operation} titles");

                expect(Gate::forUser($this->basicUser)->raw($operation))
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
        test('policy is consistent across multiple instances', function () {
            $policy1 = new TitlePolicy();
            $policy2 = new TitlePolicy();

            expect($policy1->viewAny($this->basicUser))->toBe($policy2->viewAny($this->basicUser));
        });

        test('policy is stateless', function () {
            // Multiple calls should return same results
            expect($this->policy->viewAny($this->basicUser))->toBeFalse();
            expect($this->policy->viewAny($this->basicUser))->toBeFalse();

            expect(Gate::forUser($this->admin)->raw('create'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('create'))->toBeTrue();
        });
    });
});
