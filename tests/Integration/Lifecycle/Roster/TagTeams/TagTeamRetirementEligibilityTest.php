<?php

declare(strict_types=1);

use App\Exceptions\Roster\TagTeams\CannotBeRetiredException;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException;
use App\Lifecycle\Roster\TagTeams\TagTeamRetirementEligibility;
use App\Models\Roster\TagTeams\TagTeam;

test('retirement predicate stays aligned with its guard', function (string $factoryState, bool $canRetire) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();
    $eligibility = resolve(TagTeamRetirementEligibility::class);

    expect($eligibility->canRetire($tagTeam))->toBe($canRetire);

    if ($canRetire) {
        expect(fn () => $eligibility->ensureCanRetire($tagTeam))->not->toThrow(CannotBeRetiredException::class);

        return;
    }

    expect(fn () => $eligibility->ensureCanRetire($tagTeam))->toThrow(CannotBeRetiredException::class);
})->with([
    'employed' => ['employed', true],
    'suspended' => ['suspended', true],
    'retired' => ['retired', false],
    'unemployed' => ['unemployed', false],
    'released' => ['released', false],
]);

test('unretirement predicate stays aligned with its guard', function (string $factoryState, bool $canUnretire) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();
    $eligibility = resolve(TagTeamRetirementEligibility::class);

    expect($eligibility->canUnretire($tagTeam))->toBe($canUnretire);

    if ($canUnretire) {
        expect(fn () => $eligibility->ensureCanUnretire($tagTeam))->not->toThrow(CannotBeUnretiredException::class);

        return;
    }

    expect(fn () => $eligibility->ensureCanUnretire($tagTeam))->toThrow(CannotBeUnretiredException::class);
})->with([
    'retired with current partners' => ['retired', true],
    'employed' => ['employed', false],
    'unemployed' => ['unemployed', false],
]);

test('future employment does not block unretiring a duplicate tag-team name', function () {
    $tagTeam = TagTeam::factory()->retired()->create(['name' => 'Future Conflict Team']);

    TagTeam::factory()->create(['name' => $tagTeam->name])
        ->employments()
        ->create(['started_at' => now()->addDay()]);

    expect(resolve(TagTeamRetirementEligibility::class)->canUnretire($tagTeam, requireAvailablePartners: false))
        ->toBeTrue();
});
