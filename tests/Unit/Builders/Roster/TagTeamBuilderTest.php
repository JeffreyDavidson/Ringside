<?php

declare(strict_types=1);

use App\Models\TagTeams\TagTeam;

test('bookable tag teams can be retrieved', function () {
    $futureEmployedTagTeam = TagTeam::factory()->withFutureEmployment()->create();
    $bookableTagTeam = TagTeam::factory()->bookable()->create();
    $suspendedTagTeam = TagTeam::factory()->suspended()->create();
    $retiredTagTeam = TagTeam::factory()->retired()->create();
    $releasedTagTeam = TagTeam::factory()->released()->create();
    $unemployedTagTeam = TagTeam::factory()->unemployed()->create();
    $unbookableTagTeam = TagTeam::factory()->unbookable()->create();

    $bookableTagTeams = TagTeam::bookable()->get();

    expect($bookableTagTeams)
        ->toHaveCount(1)
        ->and($bookableTagTeams->contains($bookableTagTeam))->toBeTrue();
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

test('unbookable tag teams can be retrieved', function () {
    $futureEmployedTagTeam = TagTeam::factory()->withFutureEmployment()->create();
    $bookableTagTeam = TagTeam::factory()->bookable()->create();
    $suspendedTagTeam = TagTeam::factory()->suspended()->create();
    $retiredTagTeam = TagTeam::factory()->retired()->create();
    $releasedTagTeam = TagTeam::factory()->released()->create();
    $unemployedTagTeam = TagTeam::factory()->unemployed()->create();
    $unbookableTagTeam = TagTeam::factory()->unbookable()->create();

    $unbookableTagTeams = TagTeam::unbookable()->get();

    expect($unbookableTagTeams)
        ->toHaveCount(6)
        ->and($unbookableTagTeams->contains($futureEmployedTagTeam))->toBeTrue()
        ->and($unbookableTagTeams->contains($suspendedTagTeam))->toBeTrue()
        ->and($unbookableTagTeams->contains($retiredTagTeam))->toBeTrue()
        ->and($unbookableTagTeams->contains($releasedTagTeam))->toBeTrue()
        ->and($unbookableTagTeams->contains($unemployedTagTeam))->toBeTrue()
        ->and($unbookableTagTeams->contains($unbookableTagTeam))->toBeTrue();
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
