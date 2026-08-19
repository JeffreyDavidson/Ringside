<?php

declare(strict_types=1);

use App\Actions\Referees\InjureAction;
use App\Models\Roster\Referees\Referee;
use App\Policies\ManagerPolicy;
use App\Policies\RefereePolicy;
use App\Policies\WrestlerPolicy;
use Illuminate\Support\Facades\Gate;

/**
 * Unit tests for RefereePolicy authorization logic.
 *
 * These tests focus on the authorization logic in isolation,
 * testing each permission method independently.
 *
 * @see RefereePolicy
 */
describe('RefereePolicy Unit Tests', function () {

    beforeEach(function () {
        $this->policy = new RefereePolicy();
        $this->admin = administrator();
        $this->basicUser = basicUser();
        $this->referee = Referee::factory()->create();
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

        test('global Gate hook works for referee-specific abilities', function () {
            expect(Gate::forUser($this->admin)->raw('employ'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('release'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('retire'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('unretire'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('injure'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('clearFromInjury'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('suspend'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('reinstate'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('assignToMatch'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('removeFromMatch'))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->raw('employ'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('release'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('retire'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('unretire'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('injure'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('clearFromInjury'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('suspend'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('reinstate'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('assignToMatch'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('removeFromMatch'))->toBeNull();
        });
    });

    describe('basic CRUD permissions', function () {
        test('viewAny method denies basic users', function () {
            expect($this->policy->viewAny($this->basicUser))->toBeFalse();
        });

        test('view method denies basic users', function () {
            expect($this->policy->view($this->basicUser, $this->referee))->toBeFalse();
        });

        test('create method denies basic users', function () {
            expect($this->policy->create($this->basicUser))->toBeFalse();
        });

        test('update method denies basic users', function () {
            expect($this->policy->update($this->basicUser, $this->referee))->toBeFalse();
        });

        test('delete method denies basic users', function () {
            expect($this->policy->delete($this->basicUser, $this->referee))->toBeFalse();
        });

        test('restore method denies basic users', function () {
            expect($this->policy->restore($this->basicUser, $this->referee))->toBeFalse();
        });
    });

    describe('policy integration with Laravel Gate', function () {
        test('policy integrates correctly with Gate facade', function () {
            // Test administrator permissions through Gate
            expect(Gate::forUser($this->admin)->allows('viewAny', Referee::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('create', Referee::class))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('view', $this->referee))->toBeTrue();

            // Test basic user permissions through Gate
            expect(Gate::forUser($this->basicUser)->denies('viewAny', Referee::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('create', Referee::class))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('view', $this->referee))->toBeTrue();
        });

        test('policy works with specific referee instances', function () {
            // Test with specific referee instance
            expect(Gate::forUser($this->admin)->allows('view', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('update', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('delete', $this->referee))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->denies('view', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('update', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('delete', $this->referee))->toBeTrue();
        });

        test('policy supports referee-specific operations through Gate', function () {
            // Test referee employment operations
            expect(Gate::forUser($this->admin)->allows('employ', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('release', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('retire', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('unretire', $this->referee))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->denies('employ', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('release', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('retire', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('unretire', $this->referee))->toBeTrue();

            // Test referee injury operations
            expect(Gate::forUser($this->admin)->allows('injure', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('clearFromInjury', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('suspend', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('reinstate', $this->referee))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->denies('injure', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('clearFromInjury', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('suspend', $this->referee))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('reinstate', $this->referee))->toBeTrue();

            // TODO: Add match assignment policy methods when business requirements are clarified
            // expect(Gate::forUser($this->admin)->allows('assignToMatch', $this->referee))->toBeTrue();
            // expect(Gate::forUser($this->admin)->allows('removeFromMatch', $this->referee))->toBeTrue();
            //
            // expect(Gate::forUser($this->basicUser)->denies('assignToMatch', $this->referee))->toBeTrue();
            // expect(Gate::forUser($this->basicUser)->denies('removeFromMatch', $this->referee))->toBeTrue();
        });
    });

    describe('policy method consistency', function () {
        test('all policy methods follow consistent pattern', function () {
            $methods = ['viewAny', 'view', 'create', 'update', 'delete', 'restore'];

            foreach ($methods as $method) {
                $subject = in_array($method, ['viewAny', 'create'], true) ? Referee::class : $this->referee;

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

        test('policy is similar to wrestler and manager policies', function () {
            // Referee policy should have similar methods to wrestler and manager policies
            // since they're all individual roster members
            $refereeMethods = get_class_methods($this->policy);
            $wrestlerPolicy = new WrestlerPolicy();
            $wrestlerMethods = get_class_methods($wrestlerPolicy);
            $managerPolicy = new ManagerPolicy();
            $managerMethods = get_class_methods($managerPolicy);

            // Should have the same basic structure as other individual roster member policies
            expect($refereeMethods)->not->toContain('before');
            expect(in_array('viewAny', $refereeMethods))->toBeTrue();
            expect(in_array('create', $refereeMethods))->toBeTrue();
            expect(in_array('update', $refereeMethods))->toBeTrue();
            expect(in_array('delete', $refereeMethods))->toBeTrue();
            expect(in_array('restore', $refereeMethods))->toBeTrue();
        });
    });

    describe('referee-specific business context', function () {
        test('policy supports referee lifecycle operations via the global Gate hook', function () {
            // These operations aren't explicitly defined in the policy
            // but should be allowed for administrators via the global Gate hook
            $refereeOperations = [
                'employ', 'release', 'retire', 'unretire',
                'injure', 'clearFromInjury', 'suspend', 'reinstate',
                'assignToMatch', 'removeFromMatch', 'viewMatchHistory',
            ];

            foreach ($refereeOperations as $operation) {
                expect(Gate::forUser($this->admin)->raw($operation))
                    ->toBeTrue("Administrator should be able to {$operation} referees");

                expect(Gate::forUser($this->basicUser)->raw($operation))
                    ->toBeNull("Basic user should continue to individual checks for {$operation}");
            }
        });

        test('policy works with different referee statuses', function () {
            $employedReferee = Referee::factory()->bookable()->create();
            $injuredReferee = Referee::factory()->injured()->create();
            $retiredReferee = Referee::factory()->retired()->create();
            $suspendedReferee = Referee::factory()->suspended()->create();

            // All referee statuses should follow same authorization rules
            foreach ([$employedReferee, $injuredReferee, $retiredReferee, $suspendedReferee] as $referee) {
                expect(Gate::forUser($this->admin)->allows('view', $referee))->toBeTrue();
                expect(Gate::forUser($this->basicUser)->denies('view', $referee))->toBeTrue();
            }
        });

        // TODO: Add match assignment policy methods when business requirements are clarified
        // test('policy works with referee match assignment contexts', function () {
        //     $activeReferee = Referee::factory()->bookable()->create();
        //     $injuredReferee = Referee::factory()->injured()->create();
        //
        //     // Create matches and assignments (if system supports it)
        //     $event = \App\Models\Events\Event::factory()->create();
        //     $match = \App\Models\Matches\EventMatch::factory()->for($event, 'event')->create();
        //
        //     // Both active and injured referees should follow same authorization rules
        //     // (Business logic about who can be assigned handled in Actions, not Policies)
        //     expect(Gate::forUser($this->admin)->allows('assignToMatch', $activeReferee))->toBeTrue();
        //     expect(Gate::forUser($this->admin)->allows('assignToMatch', $injuredReferee))->toBeTrue();
        //
        //     expect(Gate::forUser($this->basicUser)->denies('assignToMatch', $activeReferee))->toBeTrue();
        //     expect(Gate::forUser($this->basicUser)->denies('assignToMatch', $injuredReferee))->toBeTrue();
        // });

        // TODO: Add referee qualification and experience policy methods when business requirements are clarified
        // test('policy handles referee experience and qualification context', function () {
        //     $seniorReferee = Referee::factory()->bookable()->create();
        //     $juniorReferee = Referee::factory()->bookable()->create();
        //
        //     // Both senior and junior referees should follow same authorization pattern
        //     // (Experience-based assignment logic handled in business layer)
        //     $refereeOperations = [
        //         'assignToMainEvent', 'assignToRegularMatch', 'promoteToSenior',
        //         'viewPerformanceMetrics', 'updateQualifications'
        //     ];
        //
        //     foreach ($refereeOperations as $operation) {
        //         expect(Gate::forUser($this->admin)->allows($operation, $seniorReferee))->toBeTrue();
        //         expect(Gate::forUser($this->admin)->allows($operation, $juniorReferee))->toBeTrue();
        //
        //         expect(Gate::forUser($this->basicUser)->denies($operation, $seniorReferee))->toBeTrue();
        //         expect(Gate::forUser($this->basicUser)->denies($operation, $juniorReferee))->toBeTrue();
        //     }
        // });
    });

    describe('edge cases and security', function () {
        test('policy is consistent across multiple instances', function () {
            $policy1 = new RefereePolicy();
            $policy2 = new RefereePolicy();

            expect($policy1->viewAny($this->basicUser))->toBe($policy2->viewAny($this->basicUser));
        });

        test('policy is stateless', function () {
            // Multiple calls should return same results
            expect($this->policy->viewAny($this->basicUser))->toBeFalse();
            expect($this->policy->viewAny($this->basicUser))->toBeFalse();

            expect(Gate::forUser($this->admin)->raw('create'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('create'))->toBeTrue();
        });

        test('policy handles complex referee states consistently', function () {
            // Create referee with multiple statuses (avoid conflicting business rules)
            $complexReferee = Referee::factory()->bookable()->create();

            // Apply business-compatible status changes
            resolve(InjureAction::class)->handle($complexReferee, now());
            // Note: Cannot suspend an injured referee per business rules
            // resolve(SuspendAction::class)->handle($complexReferee, now());

            // Authorization should remain consistent regardless of complex state
            expect(Gate::forUser($this->admin)->allows('view', $complexReferee))->toBeTrue();
            expect(Gate::forUser($this->admin)->allows('update', $complexReferee))->toBeTrue();

            expect(Gate::forUser($this->basicUser)->denies('view', $complexReferee))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->denies('update', $complexReferee))->toBeTrue();
        });
    });

    // TODO: Add referee-specific business operation policy methods when requirements are clarified
    // describe('referee-specific authorization scenarios', function () {
    //     test('policy supports match officiating authorization', function () {
    //         $referee = Referee::factory()->bookable()->create();
    //
    //         $matchOperations = [
    //             'officiateSinglesMatch', 'officiateTagTeamMatch', 'officiateTitleMatch',
    //             'officiateMainEvent', 'viewMatchAssignments', 'updateMatchReport'
    //         ];
    //
    //         foreach ($matchOperations as $operation) {
    //             expect(Gate::forUser($this->admin)->allows($operation, $referee))->toBeTrue();
    //             expect(Gate::forUser($this->basicUser)->denies($operation, $referee))->toBeTrue();
    //         }
    //     });
    //
    //     test('policy handles referee certification and training authorization', function () {
    //         $referee = Referee::factory()->bookable()->create();
    //
    //         $certificationOperations = [
    //             'viewCertifications', 'updateCertifications', 'scheduleTraining',
    //             'completeCertificationTest', 'renewCertification'
    //         ];
    //
    //         foreach ($certificationOperations as $operation) {
    //             expect(Gate::forUser($this->admin)->allows($operation, $referee))->toBeTrue();
    //             expect(Gate::forUser($this->basicUser)->denies($operation, $referee))->toBeTrue();
    //         }
    //     });
    //
    //     test('policy supports referee evaluation authorization', function () {
    //         $referee = Referee::factory()->bookable()->create();
    //
    //         $evaluationOperations = [
    //             'viewPerformanceReviews', 'createPerformanceReview', 'updatePerformanceReview',
    //             'viewMatchRatings', 'updateMatchRatings', 'generatePerformanceReport'
    //         ];
    //
    //         foreach ($evaluationOperations as $operation) {
    //             expect(Gate::forUser($this->admin)->allows($operation, $referee))->toBeTrue();
    //             expect(Gate::forUser($this->basicUser)->denies($operation, $referee))->toBeTrue();
    //         }
    //     });
    //
    //     test('policy handles referee development and promotion authorization', function () {
    //         $referee = Referee::factory()->bookable()->create();
    //
    //         $developmentOperations = [
    //             'promoteReferee', 'demoteReferee', 'assignMentor', 'createDevelopmentPlan',
    //             'trackCareerProgress', 'scheduleAdvancedTraining'
    //         ];
    //
    //         foreach ($developmentOperations as $operation) {
    //             expect(Gate::forUser($this->admin)->allows($operation, $referee))->toBeTrue();
    //             expect(Gate::forUser($this->basicUser)->denies($operation, $referee))->toBeTrue();
    //         }
    //     });
    // });
});
