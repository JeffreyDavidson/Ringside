<?php

declare(strict_types=1);

use App\Models\Referees\Referee;

test('available referees can be retrieved', function () {
    $futureEmployedReferee = Referee::factory()->withFutureEmployment()->create();
    $availableReferee = Referee::factory()->bookable()->create();
    $suspendedReferee = Referee::factory()->suspended()->create();
    $retiredReferee = Referee::factory()->retired()->create();
    $releasedReferee = Referee::factory()->released()->create();
    $unemployedReferee = Referee::factory()->unemployed()->create();
    $injuredReferee = Referee::factory()->injured()->create();

    $availableReferees = Referee::available()->get();

    expect($availableReferees)
        ->toHaveCount(1)
        ->and($availableReferees->contains($availableReferee))->toBeTrue();
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
        ->and($futureEmployedReferees->contains($futureEmployedReferee))->toBeTrue();
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
        ->and($suspendedReferees->contains($suspendedReferee))->toBeTrue();
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
        ->and($releasedReferees->contains($releasedReferee))->toBeTrue();
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
        ->and($retiredReferees->contains($retiredReferee))->toBeTrue();
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
        ->and($unemployedReferees->contains($unemployedReferee))->toBeTrue();
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
        ->and($injuredReferees->contains($injuredReferee))->toBeTrue();
});
