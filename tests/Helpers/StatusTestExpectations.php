<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Custom expectation functions for status and lifecycle testing.
 *
 * These functions provide reusable assertions for common status
 * and state verification patterns in integration tests.
 */

/**
 * Expect an entity to have a specific employment status.
 *
 * @param  Model&Employable<Model>  $entity
 */
function expectEmploymentStatus(Model&Employable $entity, EmploymentStatus $expectedStatus): void
{
    $entity->refresh();
    expect($entity->getAttribute('status'))->toBe($expectedStatus);
}

/**
 * Expect an entity to transition from one status to another.
 *
 * @param  Model&Employable<Model>  $entity
 */
function expectStatusTransition(Model&Employable $entity, EmploymentStatus $fromStatus, EmploymentStatus $toStatus): void
{
    expect($entity->getAttribute('status'))->toBe($fromStatus);
    $entity->refresh();
    expect($entity->getAttribute('status'))->toBe($toStatus);
}

/**
 * Expect an entity to be in an active, bookable state.
 */
function expectToBeBookable(Wrestler|Referee|TagTeam $entity): void
{
    $entity = freshModel($entity);
    expect($entity->currentEmployment()->exists())->toBeTrue();
    expect(resolve(RosterBookingEligibility::class)->allows($entity))->toBeTrue();
    expect($entity->currentEmployment()->exists() || $entity->futureEmployment()->exists())->toBeTrue();
}

/**
 * Expect an entity to be unavailable for booking.
 */
function expectToBeUnavailable(Wrestler|Referee|TagTeam $entity): void
{
    $entity = freshModel($entity);
    expect(resolve(RosterBookingEligibility::class)->allows($entity))->toBeFalse();
}

/**
 * Expect employment lifecycle to be valid.
 *
 * @param  Model&Employable<Model>  $entity
 */
function expectValidEmploymentLifecycle(Model&Employable $entity): void
{
    $entity->refresh();

    $currentEmployment = $entity->currentEmployment()->first();

    // Verify employment record exists if employed
    if ($entity->currentEmployment()->exists()) {
        expect($currentEmployment)->not->toBeNull();
        expect($currentEmployment?->getAttribute('started_at'))->not->toBeNull();
        expect($currentEmployment?->getAttribute('ended_at'))->toBeNull();
    }

    // Verify no current employment if not employed
    if (! $entity->currentEmployment()->exists()) {
        expect($currentEmployment)->toBeNull();
    }
}

/**
 * Expect retirement state to be consistent.
 *
 * @param  Model&Retirable<Model>  $entity
 */
function expectValidRetirementState(Model&Retirable $entity): void
{
    $entity->refresh();

    if ($entity->currentRetirement()->exists()) {
        $currentRetirement = $entity->currentRetirement()->firstOrFail();
        expect($currentRetirement->getAttribute('started_at'))->not->toBeNull();
        expect($currentRetirement->getAttribute('ended_at'))->toBeNull();
    } else {
        expect($entity->currentRetirement()->first())->toBeNull();
    }
}

/**
 * Expect injury state to be consistent.
 *
 * @param  Model&Injurable<Model>  $entity
 */
function expectValidInjuryState(Model&Injurable $entity): void
{
    $entity->refresh();

    if ($entity->currentInjury()->exists()) {
        $currentInjury = $entity->currentInjury()->firstOrFail();
        expect($currentInjury->getAttribute('started_at'))->not->toBeNull();
        expect($currentInjury->getAttribute('ended_at'))->toBeNull();
    } else {
        expect($entity->currentInjury()->first())->toBeNull();
    }
}

/**
 * Expect suspension state to be consistent.
 *
 * @param  Model&Suspendable<Model>  $entity
 */
function expectValidSuspensionState(Model&Suspendable $entity): void
{
    $entity->refresh();

    if ($entity->currentSuspension()->exists()) {
        $currentSuspension = $entity->currentSuspension()->firstOrFail();
        expect($currentSuspension->getAttribute('started_at'))->not->toBeNull();
        expect($currentSuspension->getAttribute('ended_at'))->toBeNull();
    } else {
        expect($entity->currentSuspension()->first())->toBeNull();
    }
}

/**
 * Expect a complete entity state to be valid and consistent.
 *
 * @param  Model&Employable<Model>  $entity
 */
function expectValidEntityState(Model&Employable $entity): void
{
    expectValidEmploymentLifecycle($entity);

    if ($entity instanceof Retirable) {
        expectValidRetirementState($entity);
    }

    if ($entity instanceof Injurable) {
        expectValidInjuryState($entity);
    }

    if ($entity instanceof Suspendable) {
        expectValidSuspensionState($entity);
    }
}

/**
 * Expect relationship counts to match expected values.
 *
 * @param  array<string, int>  $expectedCounts
 */
function expectRelationshipCounts(Model $entity, array $expectedCounts): void
{
    foreach ($expectedCounts as $relationship => $count) {
        expect($entity->{$relationship}()->count())->toBe($count);
    }
}

/**
 * Expect tag team membership to be correctly configured.
 *
 * @param  array<string, mixed>  $expectedPivotData
 */
function expectTagTeamMembership(Wrestler $wrestler, TagTeam $tagTeam, array $expectedPivotData = []): void
{
    expect($wrestler->tagTeams()->count())->toBeGreaterThan(0);

    $relationship = $wrestler->tagTeams()->where('tag_team_id', $tagTeam->id)->firstOrFail();
    expect($relationship)->not->toBeNull();
    expect($relationship->pivot->wrestler_id)->toBe($wrestler->id);
    expect($relationship->pivot->tag_team_id)->toBe($tagTeam->id);

    foreach ($expectedPivotData as $field => $expectedValue) {
        $actualValue = $relationship->pivot->{$field};

        if ($expectedValue === null) {
            expect($actualValue)->toBeNull();
        } elseif ($expectedValue instanceof Carbon) {
            // Handle Carbon instance comparison with string format
            expect(Carbon::parse($actualValue)->format('Y-m-d H:i:s'))->toBe($expectedValue->format('Y-m-d H:i:s'));
        } elseif (is_numeric($actualValue) && is_numeric($expectedValue)) {
            expect((int) $actualValue)->toBe((int) $expectedValue);
        } else {
            expect($actualValue)->toBe($expectedValue);
        }
    }
}

/**
 * Expect current relationships to be active (no end date).
 */
function expectCurrentRelationshipsActive(Wrestler $wrestler): void
{
    $currentManagers = $wrestler->currentManagers()->get();
    foreach ($currentManagers as $manager) {
        $management = WrestlerManager::query()
            ->whereBelongsTo($wrestler)
            ->whereBelongsTo($manager)
            ->whereNull('fired_at')
            ->firstOrFail();

        expect($management->fired_at)->toBeNull();
    }

    $currentTagTeam = $wrestler->currentTagTeam;
    if ($currentTagTeam) {
        $membership = TagTeamWrestler::query()
            ->whereBelongsTo($wrestler)
            ->whereBelongsTo($currentTagTeam, 'tagTeam')
            ->whereNull('left_at')
            ->firstOrFail();

        expect($membership->left_at)->toBeNull();
    }
}

/**
 * Expect previous relationships to have end dates.
 */
function expectPreviousRelationshipsEnded(Wrestler $wrestler): void
{
    $previousManagers = $wrestler->previousManagers()->get();
    foreach ($previousManagers as $manager) {
        $management = WrestlerManager::query()
            ->whereBelongsTo($wrestler)
            ->whereBelongsTo($manager)
            ->whereNotNull('fired_at')
            ->firstOrFail();

        expect($management->fired_at)->not->toBeNull();
    }

    $previousTagTeams = $wrestler->previousTagTeams()->get();
    foreach ($previousTagTeams as $tagTeam) {
        $membership = TagTeamWrestler::query()
            ->whereBelongsTo($wrestler)
            ->whereBelongsTo($tagTeam, 'tagTeam')
            ->whereNotNull('left_at')
            ->firstOrFail();

        expect($membership->left_at)->not->toBeNull();
    }
}
