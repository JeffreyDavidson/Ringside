<?php

declare(strict_types=1);

use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\DB;

test('established stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $activeStables = Stable::query()->established()->get();

    expect($activeStables)
        ->toHaveCount(1)
        ->and($activeStables->contains($activeStable))->toBeTrue();
});

test('future established stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $futureActivatedStables = Stable::query()->withFutureEstablishment()->get();

    expect($futureActivatedStables)
        ->toHaveCount(1)
        ->and($futureActivatedStables->contains($futureActivatedStable))->toBeTrue();
});

test('disbanded stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $inactiveStables = Stable::query()->disbanded()->get();

    expect($inactiveStables)
        ->toHaveCount(1)
        ->and($inactiveStables->contains($inactiveStable))->toBeTrue();
});

test('unestablished stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $unactivatedStables = Stable::query()->unestablished()->get();

    expect($unactivatedStables)
        ->toHaveCount(1)
        ->and($unactivatedStables->contains($unactivatedStable))->toBeTrue();
});

test('projected activity status does not query per stable', function () {
    Stable::factory()->active()->create();
    Stable::factory()->retired()->create();

    $stables = Stable::query()
        ->withActivityStatusState()
        ->get();

    DB::enableQueryLog();
    DB::flushQueryLog();

    $stables->each(fn (Stable $stable) => $stable->status);

    expect(DB::getQueryLog())->toBeEmpty();
});

test('previous stables can be retrieved for a wrestler', function () {
    $wrestler = Wrestler::factory()->create();
    $previousStable = Stable::factory()->create();
    $currentStable = Stable::factory()->create();
    $previousStable->wrestlers()->attach($wrestler, [
        'joined_at' => now()->subMonths(2),
        'left_at' => now()->subMonth(),
    ]);
    $currentStable->wrestlers()->attach($wrestler, [
        'joined_at' => now(),
    ]);

    $stables = Stable::query()
        ->previousForWrestlerId($wrestler->id)
        ->get();

    expect($stables)->toHaveCount(1)
        ->and($stables->firstOrFail()->is($previousStable))->toBeTrue()
        ->and($stables->firstOrFail()->getAttribute('joined_at'))->not->toBeNull()
        ->and($stables->firstOrFail()->getAttribute('left_at'))->not->toBeNull();
});

test('previous stables can be retrieved for a tag team', function () {
    $tagTeam = TagTeam::factory()->create();
    $previousStable = Stable::factory()->create();
    $currentStable = Stable::factory()->create();
    $previousStable->tagTeams()->attach($tagTeam, [
        'joined_at' => now()->subMonths(2),
        'left_at' => now()->subMonth(),
    ]);
    $currentStable->tagTeams()->attach($tagTeam, [
        'joined_at' => now(),
    ]);

    $stables = Stable::query()
        ->previousForTagTeamId($tagTeam->id)
        ->get();

    expect($stables)->toHaveCount(1)
        ->and($stables->firstOrFail()->is($previousStable))->toBeTrue()
        ->and($stables->firstOrFail()->getAttribute('joined_at'))->not->toBeNull()
        ->and($stables->firstOrFail()->getAttribute('left_at'))->not->toBeNull();
});
