<?php

declare(strict_types=1);

use App\Exceptions\Roster\TagTeams\CannotBeDeletedException;
use App\Exceptions\Roster\TagTeams\CannotBeRestoredException;
use App\Lifecycle\Roster\TagTeams\TagTeamDeletionEligibility;
use App\Models\Roster\TagTeams\TagTeam;

test('deletion predicate stays aligned with its guard', function (string $factoryState, bool $canDelete) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();
    $eligibility = resolve(TagTeamDeletionEligibility::class);

    expect($eligibility->canDelete($tagTeam))->toBe($canDelete);

    if ($canDelete) {
        expect(fn () => $eligibility->ensureCanDelete($tagTeam))->not->toThrow(CannotBeDeletedException::class);

        return;
    }

    expect(fn () => $eligibility->ensureCanDelete($tagTeam))->toThrow(CannotBeDeletedException::class);
})->with([
    'unemployed' => ['unemployed', true],
    'released' => ['released', true],
    'employed' => ['employed', false],
    'suspended' => ['suspended', false],
    'retired' => ['retired', false],
]);

test('restoration predicate stays aligned with its guard', function (bool $deleted, bool $canRestore) {
    $tagTeam = TagTeam::factory()->create();

    if ($deleted) {
        $tagTeam->delete();
    }

    $eligibility = resolve(TagTeamDeletionEligibility::class);

    expect($eligibility->canRestore($tagTeam))->toBe($canRestore);

    if ($canRestore) {
        expect(fn () => $eligibility->ensureCanRestore($tagTeam))->not->toThrow(CannotBeRestoredException::class);

        return;
    }

    expect(fn () => $eligibility->ensureCanRestore($tagTeam))->toThrow(CannotBeRestoredException::class);
})->with([
    'deleted' => [true, true],
    'active' => [false, false],
]);

test('future employment does not block restoring a duplicate tag-team name', function () {
    $tagTeam = TagTeam::factory()->create(['name' => 'Future Conflict Team']);
    $tagTeam->delete();

    TagTeam::factory()->create(['name' => $tagTeam->name])
        ->employments()
        ->create(['started_at' => now()->addDay()]);

    expect(resolve(TagTeamDeletionEligibility::class)->canRestore($tagTeam))->toBeTrue();
});
