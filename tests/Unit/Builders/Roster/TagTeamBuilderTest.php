<?php

declare(strict_types=1);

use App\Models\TagTeams\TagTeam;

test('available tag teams with minimum wrestlers can be retrieved', function () {
    $futureEmployedTagTeam = TagTeam::factory()->withFutureEmployment()->create();
    $bookableTagTeam = TagTeam::factory()->bookable()->create();
    $suspendedTagTeam = TagTeam::factory()->suspended()->create();
    $retiredTagTeam = TagTeam::factory()->retired()->create();
    $releasedTagTeam = TagTeam::factory()->released()->create();
    $unemployedTagTeam = TagTeam::factory()->unemployed()->create();
    $unbookableTagTeam = TagTeam::factory()->unbookable()->create();
    $undersizedTagTeam = TagTeam::factory()->employed()->create();
    $undersizedTagTeam->currentWrestlers()->updateExistingPivot(
        $undersizedTagTeam->currentWrestlers()->firstOrFail(),
        ['left_at' => now()],
    );

    $availableTagTeams = TagTeam::available()->withMinimumWrestlers()->get();

    expect($availableTagTeams)
        ->toHaveCount(1)
        ->and($availableTagTeams->contains($bookableTagTeam))->toBeTrue();
});

test('future employed tag teams can be retrieved', function () {
    $futureEmployedTagTeam = TagTeam::factory()->withFutureEmployment()->create();
    $bookableTagTeam = TagTeam::factory()->bookable()->create();
    $suspendedTagTeam = TagTeam::factory()->suspended()->create();
    $retiredTagTeam = TagTeam::factory()->retired()->create();
    $releasedTagTeam = TagTeam::factory()->released()->create();
    $unemployedTagTeam = TagTeam::factory()->unemployed()->create();
    $unbookableTagTeam = TagTeam::factory()->unbookable()->create();

    $futureEmployedTagTeams = TagTeam::futureEmployed()->get();

    expect($futureEmployedTagTeams)
        ->toHaveCount(1)
        ->and($futureEmployedTagTeams->contains($futureEmployedTagTeam))->toBeTrue();
});

test('tag teams below the minimum wrestler count can be retrieved', function () {
    $futureEmployedTagTeam = TagTeam::factory()->withFutureEmployment()->create();
    $bookableTagTeam = TagTeam::factory()->bookable()->create();
    $suspendedTagTeam = TagTeam::factory()->suspended()->create();
    $retiredTagTeam = TagTeam::factory()->retired()->create();
    $releasedTagTeam = TagTeam::factory()->released()->create();
    $unemployedTagTeam = TagTeam::factory()->unemployed()->create();
    $unbookableTagTeam = TagTeam::factory()->unbookable()->create();
    $undersizedTagTeam = TagTeam::factory()->employed()->create();
    $undersizedTagTeam->currentWrestlers()->updateExistingPivot(
        $undersizedTagTeam->currentWrestlers()->firstOrFail(),
        ['left_at' => now()],
    );

    $undersizedTagTeams = TagTeam::belowMinimumWrestlers()->get();

    expect($undersizedTagTeams)
        ->toHaveCount(2)
        ->and($undersizedTagTeams->contains($unbookableTagTeam))->toBeTrue()
        ->and($undersizedTagTeams->contains($undersizedTagTeam))->toBeTrue();
});

test('released tag teams can be retrieved', function () {
    $futureEmployedTagTeam = TagTeam::factory()->withFutureEmployment()->create();
    $bookableTagTeam = TagTeam::factory()->bookable()->create();
    $suspendedTagTeam = TagTeam::factory()->suspended()->create();
    $retiredTagTeam = TagTeam::factory()->retired()->create();
    $releasedTagTeam = TagTeam::factory()->released()->create();
    $unemployedTagTeam = TagTeam::factory()->unemployed()->create();
    $unbookableTagTeam = TagTeam::factory()->unbookable()->create();

    $releasedTagTeams = TagTeam::released()->get();

    expect($releasedTagTeams)
        ->toHaveCount(1)
        ->and($releasedTagTeams->contains($releasedTagTeam))->toBeTrue();
});

test('suspended tag teams can be retrieved', function () {
    $futureEmployedTagTeam = TagTeam::factory()->withFutureEmployment()->create();
    $bookableTagTeam = TagTeam::factory()->bookable()->create();
    $suspendedTagTeam = TagTeam::factory()->suspended()->create();
    $retiredTagTeam = TagTeam::factory()->retired()->create();
    $releasedTagTeam = TagTeam::factory()->released()->create();
    $unemployedTagTeam = TagTeam::factory()->unemployed()->create();
    $unbookableTagTeam = TagTeam::factory()->unbookable()->create();

    $suspendedTagTeams = TagTeam::suspended()->get();

    expect($suspendedTagTeams)
        ->toHaveCount(1)
        ->and($suspendedTagTeams->contains($suspendedTagTeam))->toBeTrue();
});

test('retired tag teams can be retrieved', function () {
    $futureEmployedTagTeam = TagTeam::factory()->withFutureEmployment()->create();
    $bookableTagTeam = TagTeam::factory()->bookable()->create();
    $suspendedTagTeam = TagTeam::factory()->suspended()->create();
    $retiredTagTeam = TagTeam::factory()->retired()->create();
    $releasedTagTeam = TagTeam::factory()->released()->create();
    $unemployedTagTeam = TagTeam::factory()->unemployed()->create();
    $unbookableTagTeam = TagTeam::factory()->unbookable()->create();

    $retiredTagTeams = TagTeam::retired()->get();

    expect($retiredTagTeams)
        ->toHaveCount(1)
        ->and($retiredTagTeams->contains($retiredTagTeam))->toBeTrue();
});

test('unemployed tag teams can be retrieved', function () {
    $futureEmployedTagTeam = TagTeam::factory()->withFutureEmployment()->create();
    $bookableTagTeam = TagTeam::factory()->bookable()->create();
    $suspendedTagTeam = TagTeam::factory()->suspended()->create();
    $retiredTagTeam = TagTeam::factory()->retired()->create();
    $releasedTagTeam = TagTeam::factory()->released()->create();
    $unemployedTagTeam = TagTeam::factory()->unemployed()->create();
    $unbookableTagTeam = TagTeam::factory()->unbookable()->create();

    $unemployedTagTeams = TagTeam::unemployed()->get();

    expect($unemployedTagTeams)
        ->toHaveCount(2)
        ->and($unemployedTagTeams->contains($unemployedTagTeam))->toBeTrue()
        ->and($unemployedTagTeams->contains($unbookableTagTeam))->toBeTrue();
});
