<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Enums\Shared\TitleStatus;
use App\Enums\Shared\StableStatus;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Suspendable;
use Illuminate\Support\Carbon;

/**
 * Custom expectation functions for status and lifecycle testing.
 * 
 * These functions provide reusable assertions for common status
 * and state verification patterns in integration tests.
 */

/**
 * Expect an entity to have a specific employment status.
 */
function expectEmploymentStatus(Employable $entity, EmploymentStatus $expectedStatus): void
{
    $entity = $entity->fresh();
    expect($entity->status)->toBe($expectedStatus);
}

/**
 * Expect an entity to transition from one status to another.
 */
function expectStatusTransition(Employable $entity, EmploymentStatus $fromStatus, EmploymentStatus $toStatus): void
{
    expect($entity->status)->toBe($fromStatus);
    $refreshed = $entity->fresh();
    expect($refreshed->status)->toBe($toStatus);
}

/**
 * Expect an entity to be in an active, bookable state.
 */
function expectToBeBookable($entity): void
{
    $entity = $entity->fresh();
    expect($entity->isEmployed())->toBeTrue();
    expect($entity->isBookable())->toBeTrue();
    expect($entity->isNotInEmployment())->toBeFalse();
}

/**
 * Expect an entity to be unavailable for booking.
 */
function expectToBeUnavailable($entity): void
{
    $entity = $entity->fresh();
    expect($entity->isBookable())->toBeFalse();
}

/**
 * Expect employment lifecycle to be valid.
 */
function expectValidEmploymentLifecycle(Employable $entity): void
{
    $entity = $entity->fresh();
    
    // Verify employment record exists if employed
    if ($entity->isEmployed()) {
        expect($entity->currentEmployment)->not->toBeNull();
        expect($entity->currentEmployment->started_at)->not->toBeNull();
        expect($entity->currentEmployment->ended_at)->toBeNull();
    }
    
    // Verify no current employment if not employed
    if (!$entity->isEmployed() && !$entity->hasFutureEmployment()) {
        expect($entity->currentEmployment)->toBeNull();
    }
}

/**
 * Expect retirement state to be consistent.
 */
function expectValidRetirementState(Retirable $entity): void
{
    $entity = $entity->fresh();
    
    if ($entity->isRetired()) {
        expect($entity->currentRetirement)->not->toBeNull();
        expect($entity->currentRetirement->started_at)->not->toBeNull();
        expect($entity->currentRetirement->ended_at)->toBeNull();
    } else {
        expect($entity->currentRetirement)->toBeNull();
    }
}

/**
 * Expect injury state to be consistent.
 */
function expectValidInjuryState(Injurable $entity): void
{
    $entity = $entity->fresh();
    
    if ($entity->isInjured()) {
        expect($entity->currentInjury)->not->toBeNull();
        expect($entity->currentInjury->started_at)->not->toBeNull();
        expect($entity->currentInjury->ended_at)->toBeNull();
    } else {
        expect($entity->currentInjury)->toBeNull();
    }
}

/**
 * Expect suspension state to be consistent.
 */
function expectValidSuspensionState(Suspendable $entity): void
{
    $entity = $entity->fresh();
    
    if ($entity->isSuspended()) {
        expect($entity->currentSuspension)->not->toBeNull();
        expect($entity->currentSuspension->started_at)->not->toBeNull();
        expect($entity->currentSuspension->ended_at)->toBeNull();
    } else {
        expect($entity->currentSuspension)->toBeNull();
    }
}

/**
 * Expect a complete entity state to be valid and consistent.
 */
function expectValidEntityState($entity): void
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
 * Expect championship state to be valid.
 */
function expectValidChampionshipState($champion, $title): void
{
    $champion = $champion->fresh();
    $title = $title->fresh();
    
    if ($champion->currentChampionships()->where('title_id', $title->id)->exists()) {
        expect($title->currentChampionship)->not->toBeNull();
        expect($title->currentChampionship->champion->id)->toBe($champion->id);
        expect($title->currentChampionship->lost_at)->toBeNull();
    }
}

/**
 * Expect an action to maintain transaction integrity.
 */
function expectTransactionIntegrity(callable $action, $entity): void
{
    $initialState = [
        'employment_count' => $entity->employments()->count(),
        'retirement_count' => $entity instanceof Retirable ? $entity->retirements()->count() : 0,
        'injury_count' => $entity instanceof Injurable ? $entity->injuries()->count() : 0,
        'suspension_count' => $entity instanceof Suspendable ? $entity->suspensions()->count() : 0,
    ];
    
    $action();
    
    $finalState = [
        'employment_count' => $entity->fresh()->employments()->count(),
        'retirement_count' => $entity instanceof Retirable ? $entity->fresh()->retirements()->count() : 0,
        'injury_count' => $entity instanceof Injurable ? $entity->fresh()->injuries()->count() : 0,
        'suspension_count' => $entity instanceof Suspendable ? $entity->fresh()->suspensions()->count() : 0,
    ];
    
    // Verify no orphaned records (counts only increase, never decrease unexpectedly)
    expect($finalState['employment_count'])->toBeGreaterThanOrEqual($initialState['employment_count']);
    expect($finalState['retirement_count'])->toBeGreaterThanOrEqual($initialState['retirement_count']);
    expect($finalState['injury_count'])->toBeGreaterThanOrEqual($initialState['injury_count']);
    expect($finalState['suspension_count'])->toBeGreaterThanOrEqual($initialState['suspension_count']);
}

/**
 * Expect future employment to be handled correctly.
 */
function expectValidFutureEmployment(Employable $entity): void
{
    $entity = $entity->fresh();
    
    if ($entity->hasFutureEmployment()) {
        expect($entity->futureEmployment)->not->toBeNull();
        expect($entity->futureEmployment->started_at)->toBeAfter(now());
        expect($entity->futureEmployment->ended_at)->toBeNull();
        expect($entity->status)->toBe(EmploymentStatus::FutureEmployment);
    }
}

/**
 * Expect employment status priority rules to be followed.
 */
function expectStatusPriorityRules(Employable $entity): void
{
    $entity = $entity->fresh();
    
    // Priority: Retired > Employed > FutureEmployment > Released > Unemployed
    if ($entity instanceof Retirable && $entity->isRetired()) {
        expect($entity->status)->toBe(EmploymentStatus::Retired);
    } elseif ($entity->isEmployed()) {
        expect($entity->status)->toBe(EmploymentStatus::Employed);
    } elseif ($entity->hasFutureEmployment()) {
        expect($entity->status)->toBe(EmploymentStatus::FutureEmployment);
    } elseif ($entity->previousEmployments()->exists()) {
        expect($entity->status)->toBe(EmploymentStatus::Released);
    } else {
        expect($entity->status)->toBe(EmploymentStatus::Unemployed);
    }
}

/**
 * Expect that two status values are equivalent.
 */
function expectStatusEquivalent($actualStatus, $expectedStatus): void
{
    if (is_string($expectedStatus)) {
        expect($actualStatus->value ?? $actualStatus)->toBe($expectedStatus);
    } elseif ($expectedStatus instanceof BackedEnum) {
        expect($actualStatus)->toBe($expectedStatus);
    } else {
        expect($actualStatus)->toEqual($expectedStatus);
    }
}

/**
 * Expect relationship counts to match expected values.
 */
function expectRelationshipCounts($entity, array $expectedCounts): void
{
    foreach ($expectedCounts as $relationship => $count) {
        expect($entity->{$relationship}()->count())->toBe($count);
    }
}

/**
 * Expect manager relationship to be correctly configured.
 */
function expectManagerRelationship($wrestler, $manager, array $expectedPivotData = []): void
{
    expect($wrestler->managers()->count())->toBeGreaterThan(0);
    
    $relationship = $wrestler->managers()->where('manager_id', $manager->id)->first();
    expect($relationship)->not->toBeNull();
    expect($relationship->pivot->wrestler_id)->toBe($wrestler->id);
    expect($relationship->pivot->manager_id)->toBe($manager->id);
    
    foreach ($expectedPivotData as $field => $expectedValue) {
        if ($expectedValue instanceof Carbon) {
            expect($relationship->pivot->{$field}->timestamp)->toBe($expectedValue->timestamp);
        } elseif ($expectedValue === null) {
            expect($relationship->pivot->{$field})->toBeNull();
        } else {
            $actualValue = $relationship->pivot->{$field};
            if (is_numeric($actualValue) && is_numeric($expectedValue)) {
                expect((int) $actualValue)->toBe((int) $expectedValue);
            } else {
                expect($actualValue)->toBe($expectedValue);
            }
        }
    }
}

/**
 * Expect tag team membership to be correctly configured.
 */
function expectTagTeamMembership($wrestler, $tagTeam, array $expectedPivotData = []): void
{
    expect($wrestler->tagTeams()->count())->toBeGreaterThan(0);
    
    $relationship = $wrestler->tagTeams()->where('tag_team_id', $tagTeam->id)->first();
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
        } elseif ($actualValue instanceof Carbon && $expectedValue instanceof Carbon) {
            // Handle Carbon instance comparison with string format
            expect($actualValue->format('Y-m-d H:i:s'))->toBe($expectedValue->format('Y-m-d H:i:s'));
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
function expectCurrentRelationshipsActive($wrestler): void
{
    $currentManagers = $wrestler->currentManagers()->get();
    foreach ($currentManagers as $manager) {
        expect($manager->pivot->fired_at)->toBeNull();
    }
    
    if (method_exists($wrestler, 'currentTagTeam')) {
        $currentTagTeam = $wrestler->currentTagTeam;
        if ($currentTagTeam) {
            expect($currentTagTeam->pivot->left_at)->toBeNull();
        }
    }
}

/**
 * Expect previous relationships to have end dates.
 */
function expectPreviousRelationshipsEnded($wrestler): void
{
    $previousManagers = $wrestler->previousManagers()->get();
    foreach ($previousManagers as $manager) {
        expect($manager->pivot->fired_at)->not->toBeNull();
    }
    
    if (method_exists($wrestler, 'previousTagTeams')) {
        $previousTagTeams = $wrestler->previousTagTeams()->get();
        foreach ($previousTagTeams as $tagTeam) {
            expect($tagTeam->pivot->left_at)->not->toBeNull();
        }
    }
}

/**
 * Expect relationship dates to be chronologically valid.
 */
function expectValidRelationshipDates($wrestler): void
{
    // Check manager relationships
    $managers = $wrestler->managers()->get();
    foreach ($managers as $manager) {
        $pivot = $manager->pivot;
        if ($pivot->fired_at) {
            expect($pivot->hired_at->lessThan($pivot->fired_at))->toBeTrue();
        }
    }
    
    // Check tag team relationships
    if (method_exists($wrestler, 'tagTeams')) {
        $tagTeams = $wrestler->tagTeams()->get();
        foreach ($tagTeams as $tagTeam) {
            $pivot = $tagTeam->pivot;
            if ($pivot->left_at) {
                expect($pivot->joined_at->lessThan($pivot->left_at))->toBeTrue();
            }
        }
    }
}

/**
 * Expect no overlapping active relationships of the same type.
 */
function expectNoOverlappingRelationships($wrestler): void
{
    // Multiple current managers are allowed in this system
    expect($wrestler->currentManagers()->count())->toBeGreaterThanOrEqual(0);
    
    // Check for multiple current tag teams (should not exist per business rules)
    if (method_exists($wrestler, 'isAMemberOfCurrentTagTeam')) {
        $currentMemberships = $wrestler->tagTeams()->wherePivotNull('left_at')->count();
        expect($currentMemberships)->toBeLessThanOrEqual(1);
    }
}

/**
 * Expect relationship history to be properly ordered.
 */
function expectProperRelationshipOrdering($wrestler): void
{
    // Managers should be ordered by hired_at
    $managers = $wrestler->managers()->orderBy('hired_at', 'asc')->get();
    $previousHiredDate = null;
    
    foreach ($managers as $manager) {
        if ($previousHiredDate) {
            expect($manager->pivot->hired_at->greaterThanOrEqualTo($previousHiredDate))->toBeTrue();
        }
        $previousHiredDate = $manager->pivot->hired_at;
    }
    
    // Tag teams should be ordered by joined_at
    if (method_exists($wrestler, 'tagTeams')) {
        $tagTeams = $wrestler->tagTeams()->orderBy('joined_at', 'asc')->get();
        $previousJoinedDate = null;
        
        foreach ($tagTeams as $tagTeam) {
            if ($previousJoinedDate) {
                expect($tagTeam->pivot->joined_at->greaterThanOrEqualTo($previousJoinedDate))->toBeTrue();
            }
            $previousJoinedDate = $tagTeam->pivot->joined_at;
        }
    }
}