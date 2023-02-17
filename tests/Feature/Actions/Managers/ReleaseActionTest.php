<?php

use App\Actions\Managers\ReleaseAction;
use App\Enums\ManagerStatus;
use App\Exceptions\CannotBeReleasedException;
use App\Models\Manager;
use App\Models\TagTeam;
use App\Models\Wrestler;

test('invoke releases a available manager and redirects', function () {
    $manager = Manager::factory()->available()->create();

    ReleaseAction::run($manager);

    expect($manager->fresh())
        ->employments->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(ManagerStatus::RELEASED);
});

test('invoke releases an injured manager and redirects', function () {
    $manager = Manager::factory()->injured()->create();

    ReleaseAction::run($manager);

    expect($manager->fresh())
        ->injuries->last()->ended_at->not->toBeNull()
        ->employments->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(ManagerStatus::RELEASED);
});

test('invoke releases an suspended manager and redirects', function () {
    $manager = Manager::factory()->suspended()->create();

    ReleaseAction::run($manager);

    expect($manager->fresh())
        ->suspensions->last()->ended_at->not->toBeNull()
        ->employments->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(ManagerStatus::RELEASED);
});

test('Invoke releases a manager leaving their current tag teams and managers and redirects', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = Wrestler::factory()->bookable()->create();
    $manager = Manager::factory()
        ->available()
        ->hasAttached($tagTeam, ['hired_at' => now()->toDateTimeString()])
        ->hasAttached($wrestler, ['hired_at' => now()->toDateTimeString()])
        ->create();

    ReleaseAction::run($manager);

    expect($manager->fresh())
        ->tagTeams()->where('manageable_id', $tagTeam->id)->get()->last()->pivot->left_at->not->toBeNull()
        ->wrestlers()->where('manageable_id', $wrestler->id)->get()->last()->pivot->left_at->not->toBeNull();
});

test('invoke throws an exception for releasing a non releasable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    ReleaseAction::run($manager);
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
