<?php

use App\Actions\Managers\RetireAction;
use App\Enums\ManagerStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Manager;
use App\Models\TagTeam;
use App\Models\Wrestler;

test('invoke retires a retirable manager and redirects', function ($factoryState) {
    $manager = Manager::factory()->$factoryState()->create();

    RetireAction::run($manager);

    expect($manager->fresh())
        ->retirements->toHaveCount(1)
        ->status->toMatchObject(ManagerStatus::RETIRED);
})->with([
    'available',
    'injured',
    'suspended',
]);

test('invoke retires a manager leaving their current tag teams and wrestlers and redirects', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = Wrestler::factory()->bookable()->create();

    $manager = Manager::factory()
        ->available()
        ->hasAttached($tagTeam, ['hired_at' => now()->toDateTimeString()])
        ->hasAttached($wrestler, ['hired_at' => now()->toDateTimeString()])
        ->create();

    RetireAction::run($manager);

    expect($manager->fresh())
        ->tagTeams()->where('manageable_id', $tagTeam->id)->get()->last()->pivot->left_at->not->toBeNull()
        ->wrestlers()->where('manageable_id', $wrestler->id)->get()->last()->pivot->left_at->not->toBeNull();
});

test('invoke throws exception for retiring a non retirable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    RetireAction::run($manager);
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureEmployment',
    'released',
    'unemployed',
]);
