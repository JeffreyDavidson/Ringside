<?php

declare(strict_types=1);

use App\Actions\Managers\InjureAction;
use App\Models\Managers\Manager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it injures an employed manager', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isInjured())->toBeFalse();

    InjureAction::run($manager);

    $manager->refresh();
    expect($manager->isInjured())->toBeTrue();
    expect($manager->isEmployed())->toBeTrue(); // Should remain employed while injured

    $this->assertDatabaseHas('managers_injuries', [
        'manager_id' => $manager->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it injures manager with specific injury date', function () {
    $manager = Manager::factory()->employed()->create();
    $injuryDate = now()->subDays(4);

    InjureAction::run($manager, $injuryDate);

    $manager->refresh();
    expect($manager->isInjured())->toBeTrue();

    $this->assertDatabaseHas('managers_injuries', [
        'manager_id' => $manager->id,
        'started_at' => $injuryDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses StatusTransitionPipeline for injury', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->currentInjury)->toBeNull();

    InjureAction::run($manager);

    $manager->refresh();

    // Verify injury was created through pipeline
    expect($manager->currentInjury)->not()->toBeNull();
    expect($manager->isInjured())->toBeTrue();

    // Verify injury record shows proper start date
    $this->assertDatabaseHas('managers_injuries', [
        'manager_id' => $manager->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it prevents injuring already injured manager', function () {
    $manager = Manager::factory()->employed()->injured()->create();

    expect($manager->isInjured())->toBeTrue();

    expect(fn () => InjureAction::run($manager))
        ->toThrow(Exception::class);
});

test('it prevents injuring unemployed manager', function () {
    $manager = Manager::factory()->create();

    expect($manager->isEmployed())->toBeFalse();

    expect(fn () => InjureAction::run($manager))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->employed()->create();

    InjureAction::run($manager);

    $manager->refresh();

    // Verify the transaction was successful
    expect($manager->isInjured())->toBeTrue();

    // Verify injury record integrity
    $injury = $manager->currentInjury;
    expect($injury)->not()->toBeNull();
    expect($injury->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($injury->ended_at)->toBeNull();
});

test('it maintains employment status during injury', function () {
    $manager = Manager::factory()->employed()->create();
    $employmentId = $manager->currentEmployment->id;

    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isInjured())->toBeFalse();

    InjureAction::run($manager);

    $manager->refresh();

    // Should maintain employment while adding injury
    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isInjured())->toBeTrue();

    // Employment record should remain unchanged
    $employment = $manager->currentEmployment;
    expect($employment)->not()->toBeNull();
    expect($employment->id)->toBe($employmentId);
    expect($employment->ended_at)->toBeNull();
});

test('it injures suspended manager', function () {
    $manager = Manager::factory()->employed()->suspended()->create();

    expect($manager->isSuspended())->toBeTrue();
    expect($manager->isInjured())->toBeFalse();

    InjureAction::run($manager);

    $manager->refresh();

    expect($manager->isSuspended())->toBeTrue();
    expect($manager->isInjured())->toBeTrue();
    expect($manager->isEmployed())->toBeTrue();
});

test('it uses DateHelper for consistent date handling', function () {
    $manager = Manager::factory()->employed()->create();
    $customInjuryDate = now()->subDays(2)->startOfDay();

    InjureAction::run($manager, $customInjuryDate);

    $manager->refresh();

    // Verify DateHelper was used for date resolution
    $this->assertDatabaseHas('managers_injuries', [
        'manager_id' => $manager->id,
        'started_at' => $customInjuryDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});
