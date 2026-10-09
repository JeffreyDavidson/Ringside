<?php

declare(strict_types=1);

use App\Exceptions\Roster\TagTeams\CannotBeRetiredException;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException;
use App\Lifecycle\Roster\TagTeams\TagTeamRetirementEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;

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

    expect(resolve(TagTeamRetirementEligibility::class)->canUnretire($tagTeam))
        ->toBeTrue();
});

test('a retired tag team without current partners cannot be unretired', function () {
    $tagTeam = TagTeam::factory()->create();
    $tagTeam->retirements()->create(['started_at' => now()->subDays(2)]);

    expect(fn () => resolve(TagTeamRetirementEligibility::class)->ensureCanUnretire($tagTeam->refresh()))
        ->toThrow(CannotBeUnretiredException::class, 'no current partners are available');
});

test('a retired tag team with fewer than two members at retirement cannot be unretired', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    TagTeamWrestler::query()->forTagTeamId($tagTeam->id)->firstOrFail()->update(['left_at' => now()->subDays(3)]);

    expect(fn () => resolve(TagTeamRetirementEligibility::class)->ensureCanUnretire($tagTeam))
        ->toThrow(CannotBeUnretiredException::class, 'only 1 partners available');
});

test('a retired tag team cannot be unretired while a partner is injured', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $partner = $tagTeam->currentWrestlers()->firstOrFail();
    $partner->injuries()->create(['started_at' => now()->subDay()]);

    expect(fn () => resolve(TagTeamRetirementEligibility::class)->ensureCanUnretire($tagTeam))
        ->toThrow(CannotBeUnretiredException::class, $partner->name);
});

test('members at retirement include those who left on or after retirement and exclude earlier leavers and later joiners', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    [$stayed, $leftAfter] = $tagTeam->currentWrestlers()->orderBy('wrestlers.id')->get()->all();
    TagTeamWrestler::query()->forWrestlerId($leftAfter->id)->update(['left_at' => now()]);
    $leftBefore = Wrestler::factory()->create();
    $tagTeam->wrestlers()->attach($leftBefore, ['joined_at' => now()->subDays(10), 'left_at' => now()->subDays(5)]);
    $joinedLater = Wrestler::factory()->create();
    $tagTeam->wrestlers()->attach($joinedLater, ['joined_at' => now()]);

    $members = resolve(TagTeamRetirementEligibility::class)->membersAtRetirement($tagTeam);

    expect($members->modelKeys())->toBe(collect([$stayed->id, $leftAfter->id])->sort()->values()->all());
});
