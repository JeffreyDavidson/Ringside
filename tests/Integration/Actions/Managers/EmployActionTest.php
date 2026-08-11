<?php

declare(strict_types=1);

use App\Actions\Managers\EmployAction;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Models\Managers\Manager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it employs an unemployed manager', function () {
    $manager = Manager::factory()->create();

    expect($manager->isEmployed())->toBeFalse();

    resolve(EmployAction::class)->handle($manager);

    $manager->refresh();
    expect($manager->isEmployed())->toBeTrue();

    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it employs manager with specific employment date', function () {
    $manager = Manager::factory()->create();
    $employmentDate = now()->subDays(30);

    resolve(EmployAction::class)->handle($manager, $employmentDate);

    $manager->refresh();
    expect($manager->isEmployed())->toBeTrue();

    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it rejects employing a retired manager without changing retirement', function () {
    $manager = Manager::factory()->retired()->create();
    $retirement = $manager->currentRetirement()->firstOrFail();

    expect($manager->isRetired())->toBeTrue();
    expect($manager->isEmployed())->toBeFalse();

    expect(fn () => resolve(EmployAction::class)->handle($manager))
        ->toThrow(CannotBeEmployedException::class);

    $manager->refresh();
    $retirement->refresh();

    expect($manager->isEmployed())->toBeFalse()
        ->and($manager->isRetired())->toBeTrue()
        ->and($retirement->ended_at)->toBeNull();

    $this->assertDatabaseMissing('managers_employments', [
        'manager_id' => $manager->id,
        'ended_at' => null,
    ]);
});

test('it employs suspended manager and ends suspension', function () {
    $manager = Manager::factory()->suspended()->create();

    expect($manager->isSuspended())->toBeTrue();
    expect($manager->isEmployed())->toBeTrue();

    expect(fn () => resolve(EmployAction::class)->handle($manager))
        ->toThrow(Exception::class);
});

test('it employs injured manager and ends injury', function () {
    $manager = Manager::factory()->injured()->create();

    expect($manager->isInjured())->toBeTrue();
    expect($manager->isEmployed())->toBeTrue();

    expect(fn () => resolve(EmployAction::class)->handle($manager))
        ->toThrow(Exception::class);
});

test('it prevents employing already employed manager', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->isEmployed())->toBeTrue();

    expect(fn () => resolve(EmployAction::class)->handle($manager))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->create();

    expect($manager->isEmployed())->toBeFalse();

    resolve(EmployAction::class)->handle($manager);

    // Verify the transaction was successful
    $manager->refresh();
    expect($manager->isEmployed())->toBeTrue();
    expect($manager->status->value)->toBe('employed');

    // Verify employment record integrity
    $employment = $manager->currentEmployment()->firstOrFail();
    expect(requiredDate($employment->started_at)->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($employment->ended_at)->toBeNull();
});

test('it persists the employment lifecycle', function () {
    $manager = Manager::factory()->create();

    resolve(EmployAction::class)->handle($manager);

    $manager->refresh();

    // Should properly update both employment record and status field
    expect($manager->isEmployed())->toBeTrue();
    expect($manager->status->value)->toBe('employed');

    // Should create proper employment record
    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'ended_at' => null,
    ]);
});
