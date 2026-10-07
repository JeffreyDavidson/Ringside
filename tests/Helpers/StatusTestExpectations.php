<?php

declare(strict_types=1);

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
    expect($relationship)->not->toBeNull()
        ->and($relationship->pivot->wrestler_id)->toBe($wrestler->id)
        ->and($relationship->pivot->tag_team_id)->toBe($tagTeam->id);

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
