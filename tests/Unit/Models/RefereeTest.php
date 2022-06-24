<?php

use App\Enums\RefereeStatus;
use App\Models\Concerns\HasFullName;
use App\Models\Referee;
use App\Models\SingleRosterMember;
use Illuminate\Database\Eloquent\SoftDeletes;

test('a referee has a first name', function () {
    $referee = Referee::factory()->create(['first_name' => 'John']);

    expect($referee)->first_name->toBe('John');
});

test('a referee has a last name', function () {
    $referee = Referee::factory()->create(['last_name' => 'Smith']);

    expect($referee)->last_name->toBe('Smith');
});

test('a referee has a status', function () {
    $referee = Referee::factory()->create();

    expect($referee)->status->toBeInstanceOf(RefereeStatus::class);
});

test('a referee is a single roster member', function () {
    expect(get_parent_class(Referee::class))->toBe(SingleRosterMember::class);
});

test('a referee uses soft deleted trait', function () {
    expect(Referee::class)->usesTrait(SoftDeletes::class);
});

test('a referee uses has full name trait', function () {
    expect(Referee::class)->usesTrait(HasFullName::class);
});

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
