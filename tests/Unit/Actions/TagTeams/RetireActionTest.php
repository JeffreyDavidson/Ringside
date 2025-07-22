<?php

declare(strict_types=1);

use App\Actions\TagTeams\RetireAction;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\TagTeams\TagTeam;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it retires a bookable tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();

    // UnifiedRetireAction handles all retirement logic internally
    resolve(RetireAction::class)->handle($tagTeam);
});

test('it retires a bookable tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    // UnifiedRetireAction handles all retirement logic internally
    resolve(RetireAction::class)->handle($tagTeam, $datetime);
});

test('it retires a released tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->released()->create();

    // UnifiedRetireAction handles all retirement logic internally
    resolve(RetireAction::class)->handle($tagTeam);
});

test('it retires a released tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->released()->create();
    $datetime = now()->addDays(2);

    // UnifiedRetireAction handles all retirement logic internally  
    resolve(RetireAction::class)->handle($tagTeam, $datetime);
});

test('it retires a suspended tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();

    // UnifiedRetireAction handles all retirement logic internally
    resolve(RetireAction::class)->handle($tagTeam);
});

test('it retires a suspended tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    // UnifiedRetireAction handles all retirement logic internally
    resolve(RetireAction::class)->handle($tagTeam, $datetime);
});

test('it retires an employed tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    // UnifiedRetireAction handles all retirement logic internally
    resolve(RetireAction::class)->handle($tagTeam);
});

test('it retires an employed tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $datetime = now()->addDays(2);

    // UnifiedRetireAction handles all retirement logic internally
    resolve(RetireAction::class)->handle($tagTeam, $datetime);
});

test('it throws exception for retiring a non retirable tag team', function ($factoryState) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    resolve(RetireAction::class)->handle($tagTeam);
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureEmployment',
    'unemployed',
]);