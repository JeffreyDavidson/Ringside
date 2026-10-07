<?php

declare(strict_types=1);

use App\Actions\Stables\RestoreAction as RestoreStable;
use App\Actions\TagTeams\RestoreAction as RestoreTagTeam;
use App\Actions\TagTeams\UnretireAction as UnretireTagTeam;
use App\Actions\Titles\RestoreAction as RestoreTitle;
use App\Exceptions\Roster\Stables\CannotBeRestoredException as StableCannotBeRestored;
use App\Exceptions\Roster\TagTeams\CannotBeRestoredException as TagTeamCannotBeRestored;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException as TagTeamCannotBeUnretired;
use App\Exceptions\Titles\CannotBeRestoredException as TitleCannotBeRestored;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Titles\Title;

dataset('restorable named models', [
    'stable' => [Stable::class, RestoreStable::class, StableCannotBeRestored::class],
    'title' => [Title::class, RestoreTitle::class, TitleCannotBeRestored::class],
]);

it('restores a record whose name an active record of another promotion uses', function (string $model, string $action) {
    [$first, $second] = Promotion::factory()->count(2)->create()->all();
    $deleted = $model::factory()->for($first, 'promotion')->create();
    $deleted->delete();
    $model::factory()->for($second, 'promotion')->create(['name' => $deleted->name]);

    resolve($action)->handle($deleted);

    expect($deleted->refresh()->trashed())->toBeFalse();
})->with('restorable named models');

it('rejects restoring a record whose name an active record of the same promotion uses', function (string $model, string $action, string $exception) {
    $promotion = Promotion::factory()->create();
    $deleted = $model::factory()->for($promotion, 'promotion')->create();
    $deleted->delete();
    $model::factory()->for($promotion, 'promotion')->create(['name' => $deleted->name]);

    expect(fn () => resolve($action)->handle($deleted))->toThrow($exception);
})->with('restorable named models');

it('restores a tag team whose name an employed tag team of another promotion uses', function () {
    [$first, $second] = Promotion::factory()->count(2)->create()->all();
    $deleted = TagTeam::factory()->for($first, 'promotion')->create();
    $deleted->delete();
    TagTeam::factory()->for($second, 'promotion')->employed()->create(['name' => $deleted->name]);

    resolve(RestoreTagTeam::class)->handle($deleted);

    expect($deleted->refresh()->trashed())->toBeFalse();
});

it('rejects restoring a tag team whose name an employed tag team of the same promotion uses', function () {
    $promotion = Promotion::factory()->create();
    $deleted = TagTeam::factory()->for($promotion, 'promotion')->create();
    $deleted->delete();
    TagTeam::factory()->for($promotion, 'promotion')->employed()->create(['name' => $deleted->name]);

    expect(fn () => resolve(RestoreTagTeam::class)->handle($deleted))->toThrow(TagTeamCannotBeRestored::class);
});

it('unretires a tag team whose name an employed tag team of another promotion uses', function () {
    [$first, $second] = Promotion::factory()->count(2)->create()->all();
    $retired = TagTeam::factory()->for($first, 'promotion')->retired()->create();
    TagTeam::factory()->for($second, 'promotion')->employed()->create(['name' => $retired->name]);

    resolve(UnretireTagTeam::class)->handle($retired);

    expect($retired->refresh()->currentRetirement()->exists())->toBeFalse();
});

it('rejects unretiring a tag team whose name an employed tag team of the same promotion uses', function () {
    $promotion = Promotion::factory()->create();
    $retired = TagTeam::factory()->for($promotion, 'promotion')->retired()->create();
    TagTeam::factory()->for($promotion, 'promotion')->employed()->create(['name' => $retired->name]);

    expect(fn () => resolve(UnretireTagTeam::class)->handle($retired))->toThrow(TagTeamCannotBeUnretired::class);
});
