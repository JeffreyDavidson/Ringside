<?php

declare(strict_types=1);

use App\Models\Referees\Referee;

test('bookable referees can be retrieved', function () {
    $futureEmployedReferee = Referee::factory()->withFutureEmployment()->create();
    $bookableReferee = Referee::factory()->bookable()->create();
    $suspendedReferee = Referee::factory()->suspended()->create();
    $retiredReferee = Referee::factory()->retired()->create();
    $releasedReferee = Referee::factory()->released()->create();
    $unemployedReferee = Referee::factory()->unemployed()->create();
    $injuredReferee = Referee::factory()->injured()->create();

    $bookableReferees = Referee::bookable()->get();

    expect($bookableReferees)
        ->toHaveCount(1)
        ->collectionHas($bookableReferee);
});

test('future employed referees can be retrieved', function () {
    $futureEmployedReferee = Referee::factory()->withFutureEmployment()->create();
    $bookableReferee = Referee::factory()->bookable()->create();
    $suspendedReferee = Referee::factory()->suspended()->create();
    $retiredReferee = Referee::factory()->retired()->create();
    $releasedReferee = Referee::factory()->released()->create();
    $unemployedReferee = Referee::factory()->unemployed()->create();
    $injuredReferee = Referee::factory()->injured()->create();

    $futureEmployedReferees = Referee::futureEmployed()->get();

    expect($futureEmployedReferees)
        ->toHaveCount(1)
        ->collectionHas($futureEmployedReferee);
});

test('suspended referees can be retrieved', function () {
    $futureEmployedReferee = Referee::factory()->withFutureEmployment()->create();
    $bookableReferee = Referee::factory()->bookable()->create();
    $suspendedReferee = Referee::factory()->suspended()->create();
    $retiredReferee = Referee::factory()->retired()->create();
    $releasedReferee = Referee::factory()->released()->create();
    $unemployedReferee = Referee::factory()->unemployed()->create();
    $injuredReferee = Referee::factory()->injured()->create();

    $suspendedReferees = Referee::suspended()->get();

    expect($suspendedReferees)
        ->toHaveCount(1)
        ->collectionHas($suspendedReferee);
});

test('released referees can be retrieved', function () {
    $futureEmployedReferee = Referee::factory()->withFutureEmployment()->create();
    $bookableReferee = Referee::factory()->bookable()->create();
    $suspendedReferee = Referee::factory()->suspended()->create();
    $retiredReferee = Referee::factory()->retired()->create();
    $releasedReferee = Referee::factory()->released()->create();
    $unemployedReferee = Referee::factory()->unemployed()->create();
    $injuredReferee = Referee::factory()->injured()->create();

    $releasedReferees = Referee::released()->get();

    expect($releasedReferees)
        ->toHaveCount(1)
        ->collectionHas($releasedReferee);
});

test('retired referees can be retrieved', function () {
    $futureEmployedReferee = Referee::factory()->withFutureEmployment()->create();
    $bookableReferee = Referee::factory()->bookable()->create();
    $suspendedReferee = Referee::factory()->suspended()->create();
    $retiredReferee = Referee::factory()->retired()->create();
    $releasedReferee = Referee::factory()->released()->create();
    $unemployedReferee = Referee::factory()->unemployed()->create();
    $injuredReferee = Referee::factory()->injured()->create();

    $retiredReferees = Referee::retired()->get();

    expect($retiredReferees)
        ->toHaveCount(1)
        ->collectionHas($retiredReferee);
});

test('unemployed referees can be retrieved', function () {
    $futureEmployedReferee = Referee::factory()->withFutureEmployment()->create();
    $bookableReferee = Referee::factory()->bookable()->create();
    $suspendedReferee = Referee::factory()->suspended()->create();
    $retiredReferee = Referee::factory()->retired()->create();
    $releasedReferee = Referee::factory()->released()->create();
    $unemployedReferee = Referee::factory()->unemployed()->create();
    $injuredReferee = Referee::factory()->injured()->create();

    $unemployedReferees = Referee::unemployed()->get();

    expect($unemployedReferees)
        ->toHaveCount(1)
        ->collectionHas($unemployedReferee);
});

test('injured referees can be retrieved', function () {
    $futureEmployedReferee = Referee::factory()->withFutureEmployment()->create();
    $bookableReferee = Referee::factory()->bookable()->create();
    $suspendedReferee = Referee::factory()->suspended()->create();
    $retiredReferee = Referee::factory()->retired()->create();
    $releasedReferee = Referee::factory()->released()->create();
    $unemployedReferee = Referee::factory()->unemployed()->create();
    $injuredReferee = Referee::factory()->injured()->create();

    $injuredReferees = Referee::injured()->get();

    expect($injuredReferees)
        ->toHaveCount(1)
        ->collectionHas($injuredReferee);
});
