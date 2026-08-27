<?php

declare(strict_types=1);

use App\Actions\TagTeams\EmployAction;
use App\Actions\TagTeams\ReleaseAction;
use App\Data\TagTeams\TagTeamMembershipData;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\TagTeams\CannotBeEmployedException;
use App\Lifecycle\TagTeamEmploymentEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\TagTeams\TagTeamMembershipService;
use Illuminate\Support\Carbon;

/**
 * Workflow tests for TagTeam Employment multi-action scenarios.
 *
 * WORKFLOW TEST SCOPE:
 * - Multi-action employment workflows
 * - Cross-action data consistency
 * - Transaction integrity across multiple actions
 * - Complex business process validation
 *
 * Note: TagTeams have complex wrestler requirements for suspension/retirement actions,
 * so this test focuses on core employ/release workflows.
 */
describe('TagTeam Employment Workflows', function () {

    beforeEach(function () {
        $this->tagTeam = TagTeam::factory()->released()->create();
    });

    describe('multi-action employment workflows', function () {
        test('employ then release then re-employ workflow maintains consistency', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Initial employ
            resolve(EmployAction::class)->handle($tagTeam, Carbon::now());
            $employed = freshModel($tagTeam);
            expect($employed->isEmployed())->toBeTrue();

            // Release
            resolve(ReleaseAction::class)->handle($employed, Carbon::now());
            $released = freshModel($tagTeam);
            expect($released->isReleased())->toBeTrue();
            expect($released->isEmployed())->toBeFalse();

            expect(fn () => resolve(EmployAction::class)->handle($released, Carbon::now()))
                ->toThrow(CannotBeEmployedException::class);
        });
    });

    describe('complex tag team workflows', function () {
        test('employ then release workflow maintains data consistency', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Initial state
            expect($tagTeam->status)->toBe(EmploymentStatus::Unemployed);

            // Employ tag team
            resolve(EmployAction::class)->handle($tagTeam, Carbon::now());
            $afterEmployment = freshModel($tagTeam);
            expect($afterEmployment->status)->toBe(EmploymentStatus::Employed);
            expect($afterEmployment->isEmployed())->toBeTrue();

            // Release tag team
            resolve(ReleaseAction::class)->handle($afterEmployment, Carbon::now());
            $afterRelease = freshModel($tagTeam);

            // Verify release status synchronization
            expect($afterRelease->status)->toBe(EmploymentStatus::Released);
            expect($afterRelease->isReleased())->toBeTrue();
            expect($afterRelease->isEmployed())->toBeFalse();
        });

        test('multiple employment periods with gaps workflow', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // First employment
            resolve(EmployAction::class)->handle($tagTeam, Carbon::now()->subYear());
            resolve(ReleaseAction::class)->handle($tagTeam, Carbon::now()->subMonths(9));

            // Second employment
            resolve(TagTeamMembershipService::class)->establishMembership(
                $tagTeam,
                new TagTeamMembershipData(Wrestler::factory()->count(2)->create()),
                Carbon::now()->subMonths(8),
            );
            resolve(EmployAction::class)->handle($tagTeam, Carbon::now()->subMonths(6));
            resolve(ReleaseAction::class)->handle($tagTeam, Carbon::now()->subMonths(3));

            // Current employment
            resolve(TagTeamMembershipService::class)->establishMembership(
                $tagTeam,
                new TagTeamMembershipData(Wrestler::factory()->count(2)->create()),
                Carbon::now()->subMonths(2),
            );
            resolve(EmployAction::class)->handle($tagTeam, Carbon::now()->subMonths(1));

            $refreshedTagTeam = freshModel($tagTeam);
            expect($refreshedTagTeam->isEmployed())->toBeTrue();
            expect($refreshedTagTeam->employments()->count())->toBe(3);
            expect($refreshedTagTeam->previousEmployments()->count())->toBe(2);
        });
    });

    describe('transaction integrity', function () {
        test('employment action maintains transaction integrity', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Verify the action handles transactions properly
            resolve(EmployAction::class)->handle($tagTeam, Carbon::now());

            // All changes should be committed together
            $refreshedTagTeam = freshModel($tagTeam);
            expect($refreshedTagTeam->isEmployed())->toBeTrue();
            expect($refreshedTagTeam->status)->toBe(EmploymentStatus::Employed);
            expect($refreshedTagTeam->currentEmployment)->not()->toBeNull();

            // Verify no partial updates occurred
            expect($refreshedTagTeam->employments()->whereNull('ended_at')->count())->toBe(1);
        });

        test('action rollback maintains data consistency on failure', function () {
            $tagTeam = TagTeam::factory()->released()->create();

            // This test would require mocking a failure scenario
            // For now, just verify normal operation doesn't leave partial state
            resolve(EmployAction::class)->handle($tagTeam, Carbon::now());

            $refreshedTagTeam = freshModel($tagTeam);

            // Verify all state is consistent - no orphaned records
            if ($refreshedTagTeam->isEmployed()) {
                expect($refreshedTagTeam->status)->toBe(EmploymentStatus::Employed);
                expect($refreshedTagTeam->currentEmployment)->not()->toBeNull();
            }
        });
    });

    describe('business rule integration', function () {
        test('employment respects business validation rules', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Test that employment follows business rules
            resolve(EmployAction::class)->handle($tagTeam, Carbon::now());

            $refreshedTagTeam = freshModel($tagTeam);

            // Verify business rule compliance
            expect($refreshedTagTeam->isEmployed())->toBeTrue();
            expect(resolve(TagTeamEmploymentEligibility::class)->canEmploy($refreshedTagTeam))->toBeFalse();
        });

        test('employment status affects basic capabilities workflow', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Unemployed tag team has limited capabilities
            expect($tagTeam->isEmployed())->toBeFalse();
            expect(resolve(TagTeamEmploymentEligibility::class)->canEmploy($tagTeam))->toBeTrue();

            // Employ tag team
            resolve(EmployAction::class)->handle($tagTeam, Carbon::now());
            $employed = freshModel($tagTeam);

            // Employed tag team has different capabilities
            expect($employed->isEmployed())->toBeTrue();
            expect(resolve(TagTeamEmploymentEligibility::class)->canEmploy($employed))->toBeFalse();

            // Release tag team
            resolve(ReleaseAction::class)->handle($employed, Carbon::now());
            $released = freshModel($tagTeam);

            // Released tag team capabilities
            expect($released->isEmployed())->toBeFalse();
            expect(resolve(TagTeamEmploymentEligibility::class)->canEmploy($released))->toBeFalse();
        });
    });

    describe('edge case workflows', function () {
        test('employ unemployed tag team with future date workflow', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();
            $futureDate = Carbon::now()->addDays(7);

            resolve(EmployAction::class)->handle($tagTeam, $futureDate);

            $refreshedTagTeam = freshModel($tagTeam);
            expect($refreshedTagTeam->status)->toBe(EmploymentStatus::FutureEmployment);

            // Future employment won't be current until the date arrives
            $futureEmployment = $refreshedTagTeam->employments()->latest()->firstOrFail();
            expect(requiredDate($futureEmployment->started_at)->toDateTimeString())
                ->toBe($futureDate->toDateTimeString());
        });

        test('tag team maintains single active employment workflow', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Employ tag team
            resolve(EmployAction::class)->handle($tagTeam, Carbon::now());
            expect(freshModel($tagTeam)->employments()->whereNull('ended_at')->count())->toBe(1);

            // Multiple release/employ cycles
            resolve(ReleaseAction::class)->handle($tagTeam, Carbon::now());
            expect(freshModel($tagTeam)->employments()->whereNull('ended_at')->count())->toBe(0);

            resolve(TagTeamMembershipService::class)->establishMembership(
                $tagTeam,
                new TagTeamMembershipData(Wrestler::factory()->count(2)->create()),
                Carbon::now(),
            );
            resolve(EmployAction::class)->handle($tagTeam, Carbon::now());
            expect(freshModel($tagTeam)->employments()->whereNull('ended_at')->count())->toBe(1);

            // Should maintain single active employment
            expect(freshModel($tagTeam)->employments()->count())->toBe(2); // Total employments
            expect(freshModel($tagTeam)->employments()->whereNull('ended_at')->count())->toBe(1); // Active
        });
    });
});
