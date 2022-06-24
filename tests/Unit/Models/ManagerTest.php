<?php

use App\Enums\ManagerStatus;
use App\Models\Concerns\HasFullName;
use App\Models\Manager;
use App\Models\SingleRosterMember;
use Illuminate\Database\Eloquent\SoftDeletes;

test('a manager has a first name', function () {
    $manager = Manager::factory()->create(['first_name' => 'John']);

    expect($manager)->first_name->toBe('John');
});

test('a manager has a last name', function () {
    $manager = Manager::factory()->create(['last_name' => 'Smith']);

    expect($manager)->last_name->toBe('Smith');
});

test('a manager has a status', function () {
    $manager = Manager::factory()->create();

    expect($manager)->status->toBeInstanceOf(ManagerStatus::class);
});

test('a manager is a single roster member', function () {
    expect(get_parent_class(Manager::class))->toBe(SingleRosterMember::class);
});

test('a manager uses soft deleted trait', function () {
    expect(Manager::class)->usesTrait(SoftDeletes::class);
});

test('a manager uses has full name trait', function () {
    expect(Manager::class)->usesTrait(HasFullName::class);
});

test('available managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->available()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $availableManagers = Manager::available()->get();

    expect($availableManagers)
        ->toHaveCount(1)
        ->collectionHas($availableManager);
});

test('future employed managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->available()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $futureEmployedManagers = Manager::futureEmployed()->get();

    expect($futureEmployedManagers)
        ->toHaveCount(1)
        ->collectionHas($futureEmployedManager);
});

test('suspended managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->available()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $suspendedManagers = Manager::suspended()->get();

    expect($suspendedManagers)
        ->toHaveCount(1)
        ->collectionHas($suspendedManager);
});

test('released managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->available()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $releasedManagers = Manager::released()->get();

    expect($releasedManagers)
        ->toHaveCount(1)
        ->collectionHas($releasedManager);
});

test('retired managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->available()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $retiredManagers = Manager::retired()->get();

    expect($retiredManagers)
        ->toHaveCount(1)
        ->collectionHas($retiredManager);
});

test('unemployed managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->available()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $unemployedManagers = Manager::unemployed()->get();

    expect($unemployedManagers)
        ->toHaveCount(1)
        ->collectionHas($unemployedManager);
});

test('injured managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->available()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $injuredManagers = Manager::injured()->get();

    expect($injuredManagers)
        ->toHaveCount(1)
        ->collectionHas($injuredManager);
});
