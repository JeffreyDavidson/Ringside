<?php

declare(strict_types=1);

use App\Actions\Wrestlers\EmployAction;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it employs an unemployed wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    expect($wrestler->isEmployed())->toBeFalse();

    resolve(EmployAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->isEmployed())->toBeTrue();

    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it employs wrestler with specific employment date', function () {
    $wrestler = Wrestler::factory()->create();
    $employmentDate = now()->subDays(30);

    resolve(EmployAction::class)->handle($wrestler, $employmentDate);

    $wrestler->refresh();
    expect($wrestler->isEmployed())->toBeTrue();

    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it employs suspended wrestler and ends suspension', function () {
    $wrestler = Wrestler::factory()->suspended()->create();

    expect($wrestler->isSuspended())->toBeTrue();
    expect($wrestler->isEmployed())->toBeTrue();

    expect(fn () => resolve(EmployAction::class)->handle($wrestler))
        ->toThrow(Exception::class);
});

test('it employs injured wrestler and ends injury', function () {
    $wrestler = Wrestler::factory()->injured()->create();

    expect($wrestler->isInjured())->toBeTrue();
    expect($wrestler->isEmployed())->toBeTrue();

    expect(fn () => resolve(EmployAction::class)->handle($wrestler))
        ->toThrow(Exception::class);
});

test('it employs wrestler and also employs unemployed managers', function () {
    $wrestler = Wrestler::factory()->create();
    $manager1 = Manager::factory()->create(); // unemployed
    $manager2 = Manager::factory()->employed()->create(); // already employed

    // Assign managers to wrestler
    $wrestler->managers()->attach($manager1->id, ['hired_at' => now()->subDays(10)]);
    $wrestler->managers()->attach($manager2->id, ['hired_at' => now()->subDays(5)]);

    expect($wrestler->isEmployed())->toBeFalse();
    expect($manager1->isEmployed())->toBeFalse();
    expect($manager2->isEmployed())->toBeTrue();

    resolve(EmployAction::class)->handle($wrestler);

    $wrestler->refresh();
    $manager1->refresh();
    $manager2->refresh();

    expect($wrestler->isEmployed())->toBeTrue();
    expect($manager1->isEmployed())->toBeTrue(); // Should now be employed
    expect($manager2->isEmployed())->toBeTrue(); // Should remain employed

    // Both wrestler and manager1 should have new employment records
    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => null,
    ]);

    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager1->id,
        'ended_at' => null,
    ]);
});

test('it prevents employing already employed wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->isEmployed())->toBeTrue();

    expect(fn () => resolve(EmployAction::class)->handle($wrestler))
        ->toThrow(Exception::class);
});

test('it rejects employing a retired wrestler without changing retirement', function () {
    $wrestler = Wrestler::factory()->retired()->create();
    $retirement = $wrestler->currentRetirement()->firstOrFail();

    expect(fn () => resolve(EmployAction::class)->handle($wrestler))
        ->toThrow(CannotBeEmployedException::class);

    $wrestler->refresh();
    $retirement->refresh();

    expect($wrestler->isEmployed())->toBeFalse()
        ->and($wrestler->isRetired())->toBeTrue()
        ->and($retirement->ended_at)->toBeNull();

    $this->assertDatabaseMissing('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => null,
    ]);
});
