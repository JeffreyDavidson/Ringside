<?php

use App\Models\TagTeam;

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
        ->collectionHas($bookableTagTeam);
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
        ->collectionHas($futureEmployedTagTeam);
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
        ->toHaveCount(1)
        ->collectionHas($unbookableTagTeam);
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
        ->collectionHas($releasedTagTeam);
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
        ->collectionHas($suspendedTagTeam);
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
        ->collectionHas($retiredTagTeam);
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
        ->toHaveCount(1)
        ->collectionHas($unemployedTagTeam);
});
