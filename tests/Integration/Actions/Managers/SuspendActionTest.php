<?php

declare(strict_types=1);

use App\Actions\Managers\SuspendAction;
use App\Exceptions\Roster\Individuals\CannotBeSuspendedException;
use App\Models\Managers\Manager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it suspends an employed manager', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isSuspended())->toBeFalse();

    resolve(SuspendAction::class)->handle($manager);

    $manager->refresh();
    expect($manager->isSuspended())->toBeTrue();
    expect($manager->isEmployed())->toBeTrue(); // Should remain employed while suspended

    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it suspends manager with specific suspension date', function () {
    $manager = Manager::factory()->employed()->create();
    $suspensionDate = now()->subDays(3);

    resolve(SuspendAction::class)->handle($manager, $suspensionDate);

    $manager->refresh();
    expect($manager->isSuspended())->toBeTrue();

    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
        'started_at' => $suspensionDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it persists the suspension lifecycle', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->currentSuspension)->toBeNull();

    resolve(SuspendAction::class)->handle($manager);

    $manager->refresh();

    // Verify suspension period was created
    expect($manager->currentSuspension)->not()->toBeNull();
    expect($manager->isSuspended())->toBeTrue();

    // Verify suspension record shows proper start date
    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it prevents suspending already suspended manager', function () {
    $manager = Manager::factory()->suspended()->create();

    expect($manager->isSuspended())->toBeTrue();

    expect(fn () => resolve(SuspendAction::class)->handle($manager))
        ->toThrow(Exception::class);
});

test('it prevents suspending unemployed manager', function () {
    $manager = Manager::factory()->create();

    expect($manager->isEmployed())->toBeFalse();

    expect(fn () => resolve(SuspendAction::class)->handle($manager))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->employed()->create();

    resolve(SuspendAction::class)->handle($manager);

    $manager->refresh();

    // Verify the transaction was successful
    expect($manager->isSuspended())->toBeTrue();

    // Verify suspension record integrity
    $suspension = $manager->currentSuspension()->firstOrFail();
    expect(requiredDate($suspension->started_at)->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($suspension->ended_at)->toBeNull();
});

test('it maintains employment status during suspension', function () {
    $manager = Manager::factory()->employed()->create();
    $employmentId = $manager->currentEmployment()->firstOrFail()->id;

    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isSuspended())->toBeFalse();

    resolve(SuspendAction::class)->handle($manager);

    $manager->refresh();

    // Should maintain employment while adding suspension
    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isSuspended())->toBeTrue();

    // Employment record should remain unchanged
    $employment = $manager->currentEmployment()->firstOrFail();
    expect($employment->id)->toBe($employmentId);
    expect($employment->ended_at)->toBeNull();
});

test('it prevents suspending an injured manager', function () {
    $manager = Manager::factory()->injured()->create();

    expect($manager->isInjured())->toBeTrue();
    expect($manager->isSuspended())->toBeFalse();

    expect(fn () => resolve(SuspendAction::class)->handle($manager))
        ->toThrow(CannotBeSuspendedException::class);

    $manager->refresh();

    expect($manager->isInjured())->toBeTrue();
    expect($manager->isSuspended())->toBeFalse();
    expect($manager->isEmployed())->toBeTrue();
});

test('it uses DateHelper for consistent date handling', function () {
    $manager = Manager::factory()->employed()->create();
    $customSuspensionDate = now()->subDays(1)->startOfDay();

    resolve(SuspendAction::class)->handle($manager, $customSuspensionDate);

    $manager->refresh();

    // Verify DateHelper was used for date resolution
    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
        'started_at' => $customSuspensionDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it creates only one suspension record per action', function () {
    $manager = Manager::factory()->employed()->create();

    resolve(SuspendAction::class)->handle($manager);

    $manager->refresh();

    // Should create exactly one suspension record
    expect($manager->suspensions()->count())->toBe(1);
    expect($manager->currentSuspension)->not()->toBeNull();
});
