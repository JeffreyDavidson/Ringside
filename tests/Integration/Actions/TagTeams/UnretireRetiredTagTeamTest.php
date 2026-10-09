<?php

declare(strict_types=1);

use App\Actions\TagTeams\RetireAction as RetireTagTeamAction;
use App\Actions\TagTeams\UnretireAction as UnretireTagTeamAction;
use App\Actions\Wrestlers\RetireAction as RetireWrestlerAction;
use App\Actions\Wrestlers\UnretireAction as UnretireWrestlerAction;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;

/**
 * Builds a tag team that retired through the application, so its members went through the retire cascade.
 *
 * @return array{TagTeam, Wrestler, Wrestler}
 */
function retiredThroughAction(): array
{
    $tagTeam = TagTeam::factory()->employed()->create();

    resolve(RetireTagTeamAction::class)->handle($tagTeam, now()->subDays(2));

    $wrestlers = $tagTeam->currentWrestlers()->orderBy('wrestlers.id')->get();

    $first = $wrestlers->firstOrFail();

    return [$tagTeam->refresh(), $first, $wrestlers->sole(fn (Wrestler $wrestler): bool => $wrestler->id !== $first->id)];
}

test('retiring a tag team retires its wrestlers and keeps them as current members', function () {
    [$tagTeam, $first, $second] = retiredThroughAction();

    expect($tagTeam->currentWrestlers()->count())->toBe(2)
        ->and($first->currentRetirement()->exists())->toBeTrue()
        ->and($second->currentRetirement()->exists())->toBeTrue();
});

test('retiring a tag team still ends the wrestlers other relationships', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $wrestler = $tagTeam->currentWrestlers()->firstOrFail();
    $stable = Stable::factory()->create();
    $stable->wrestlers()->attach($wrestler, ['joined_at' => now()->subYear()]);

    resolve(RetireTagTeamAction::class)->handle($tagTeam);

    expect($stable->currentWrestlers()->exists())->toBeFalse()
        ->and($tagTeam->currentWrestlers()->exists())->toBeTrue();
});

test('a tag team retired through the app can be unretired with its wrestlers', function () {
    [$tagTeam, $first, $second] = retiredThroughAction();

    resolve(UnretireTagTeamAction::class)->handle($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->currentRetirement()->exists())->toBeFalse()
        ->and($tagTeam->currentEmployment()->exists())->toBeTrue()
        ->and($tagTeam->currentWrestlers()->count())->toBe(2)
        ->and($first->refresh()->currentEmployment()->exists())->toBeTrue()
        ->and($second->refresh()->currentEmployment()->exists())->toBeTrue()
        ->and($first->currentRetirement()->exists())->toBeFalse()
        ->and($second->currentRetirement()->exists())->toBeFalse();
});

test('a wrestler unretiring alone leaves the retired tag team and rejoins it when the team is unretired', function () {
    [$tagTeam, $first, $second] = retiredThroughAction();

    resolve(UnretireWrestlerAction::class)->handle($first);

    expect(TagTeamWrestler::query()->current()->forWrestlerId($first->id)->exists())->toBeFalse()
        ->and($first->refresh()->currentEmployment()->exists())->toBeTrue();

    resolve(UnretireTagTeamAction::class)->handle($tagTeam->refresh());

    expect($tagTeam->refresh()->currentWrestlers()->pluck('wrestlers.id')->sort()->values()->all())
        ->toBe([$first->id, $second->id])
        ->and(TagTeamWrestler::query()->forTagTeamId($tagTeam->id)->forWrestlerId($first->id)->count())->toBe(2)
        ->and($tagTeam->currentEmployment()->exists())->toBeTrue()
        ->and($second->refresh()->currentEmployment()->exists())->toBeTrue()
        ->and($second->currentRetirement()->exists())->toBeFalse();
});

test('a wrestler who came back alone and joined another tag team blocks unretiring the team', function () {
    [$tagTeam, $first, $second] = retiredThroughAction();
    resolve(UnretireWrestlerAction::class)->handle($first);
    $otherTeam = TagTeam::factory()->create();
    $otherTeam->wrestlers()->attach($first, ['joined_at' => now()]);

    expect(fn () => resolve(UnretireTagTeamAction::class)->handle($tagTeam->refresh()))
        ->toThrow(CannotBeUnretiredException::class, $first->name)
        ->and($tagTeam->refresh()->currentRetirement()->exists())->toBeTrue()
        ->and($tagTeam->currentEmployment()->exists())->toBeFalse()
        ->and($second->refresh()->currentRetirement()->exists())->toBeTrue();
});

test('a soft-deleted wrestler blocks unretiring the team', function () {
    [$tagTeam, $first, $second] = retiredThroughAction();
    $first->delete();

    expect(fn () => resolve(UnretireTagTeamAction::class)->handle($tagTeam->refresh()))
        ->toThrow(CannotBeUnretiredException::class, $first->name)
        ->and($tagTeam->refresh()->currentRetirement()->exists())->toBeTrue()
        ->and($second->refresh()->currentRetirement()->exists())->toBeTrue();
});

test('a wrestler retired on their own still leaves the tag team', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $wrestler = $tagTeam->currentWrestlers()->firstOrFail();

    resolve(RetireWrestlerAction::class)->handle($wrestler);

    expect(TagTeamWrestler::query()->current()->forWrestlerId($wrestler->id)->exists())->toBeFalse()
        ->and($tagTeam->currentWrestlers()->count())->toBe(1);
});
