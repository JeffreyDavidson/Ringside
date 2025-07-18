<?php

declare(strict_types=1);

use App\Actions\Managers\RetireAction as ManagerRetireAction;
use App\Actions\Stables\RetireAction;
use App\Actions\TagTeams\RetireAction as TagTeamRetireAction;
use App\Actions\Wrestlers\RetireAction as WrestlerRetireAction;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\StableRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it retires an active stable at the current datetime by default', function () {
    $stable = Stable::factory()->active()->create();
    
    // Verify stable is active before retirement
    expect($stable->isCurrentlyActive())->toBeTrue();
    expect($stable->isRetired())->toBeFalse();

    // Call the action
    resolve(RetireAction::class)->handle($stable);

    // Verify stable is retired after action
    $stable->refresh();
    expect($stable->isRetired())->toBeTrue();
    expect($stable->isCurrentlyActive())->toBeFalse();
});

test('it retires an active stable at a specific datetime', function () {
    $stable = Stable::factory()->active()->create();
    $datetime = now()->addDays(2);

    // Verify stable is active before retirement
    expect($stable->isCurrentlyActive())->toBeTrue();
    expect($stable->isRetired())->toBeFalse();

    // Call the action
    resolve(RetireAction::class)->handle($stable, $datetime);

    // Verify stable is retired after action
    $stable->refresh();
    expect($stable->isRetired())->toBeTrue();
    expect($stable->isCurrentlyActive())->toBeFalse();
});

test('it retires an inactive stable at the current datetime by default', function () {
    $stable = Stable::factory()->inactive()->create();

    // Verify stable is inactive before retirement
    expect($stable->isCurrentlyActive())->toBeFalse();
    expect($stable->isRetired())->toBeFalse();

    // Call the action
    resolve(RetireAction::class)->handle($stable);

    // Verify stable is retired after action
    $stable->refresh();
    expect($stable->isRetired())->toBeTrue();
    expect($stable->isCurrentlyActive())->toBeFalse();
});

test('it retires an inactive stable at a specific datetime', function () {
    $stable = Stable::factory()->inactive()->create();
    $datetime = now()->addDays(2);

    // Verify stable is inactive before retirement
    expect($stable->isCurrentlyActive())->toBeFalse();
    expect($stable->isRetired())->toBeFalse();

    // Call the action
    resolve(RetireAction::class)->handle($stable, $datetime);

    // Verify stable is retired after action
    $stable->refresh();
    expect($stable->isRetired())->toBeTrue();
    expect($stable->isCurrentlyActive())->toBeFalse();
});

test('it retires the current tag teams and current wrestlers of a stable', function () {
    $stable = Stable::factory()->active()->create();
    
    // Verify stable has current members before retirement
    expect($stable->currentWrestlers()->count())->toBeGreaterThan(0);
    expect($stable->currentTagTeams()->count())->toBeGreaterThan(0);
    
    // Get the current members before retirement
    $currentWrestlers = $stable->currentWrestlers;
    $currentTagTeams = $stable->currentTagTeams;
    
    // Verify they are not retired before action
    foreach ($currentWrestlers as $wrestler) {
        expect($wrestler->isRetired())->toBeFalse();
    }
    foreach ($currentTagTeams as $tagTeam) {
        expect($tagTeam->isRetired())->toBeFalse();
    }

    // Call the action
    resolve(RetireAction::class)->handle($stable);

    // Verify stable is retired
    $stable->refresh();
    expect($stable->isRetired())->toBeTrue();
    
    // Verify current members were retired
    foreach ($currentWrestlers as $wrestler) {
        $wrestler->refresh();
        expect($wrestler->isRetired())->toBeTrue();
    }
    foreach ($currentTagTeams as $tagTeam) {
        $tagTeam->refresh();
        expect($tagTeam->isRetired())->toBeTrue();
    }
});

test('it throws exception trying to retire a non retirable stable', function ($factoryState) {
    $stable = Stable::factory()->{$factoryState}()->create();

    resolve(RetireAction::class)->handle($stable);
})->throws(CannotBeRetiredException::class)->with([
    'unactivated',
    'withFutureActivation',
    'retired',
]);
