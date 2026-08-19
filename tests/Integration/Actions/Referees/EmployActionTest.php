<?php

declare(strict_types=1);

use App\Actions\Referees\EmployAction;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Models\Roster\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it employs an unemployed referee', function () {
    $referee = Referee::factory()->create();

    expect($referee->isEmployed())->toBeFalse();

    resolve(EmployAction::class)->handle($referee);

    $referee->refresh();
    expect($referee->isEmployed())->toBeTrue();

    $this->assertDatabaseHas('employments', [
        'employable_id' => $referee->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it employs referee with specific employment date', function () {
    $referee = Referee::factory()->create();
    $employmentDate = now()->subDays(30);

    resolve(EmployAction::class)->handle($referee, $employmentDate);

    $referee->refresh();
    expect($referee->isEmployed())->toBeTrue();

    $this->assertDatabaseHas('employments', [
        'employable_id' => $referee->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it prevents re-employing suspended referee', function () {
    $referee = Referee::factory()->suspended()->create();

    expect($referee->isSuspended())->toBeTrue();
    expect($referee->isEmployed())->toBeTrue();

    expect(fn () => resolve(EmployAction::class)->handle($referee))
        ->toThrow(CannotBeEmployedException::class);
});

test('it prevents re-employing injured referee', function () {
    $referee = Referee::factory()->injured()->create();

    expect($referee->isInjured())->toBeTrue();
    expect($referee->isEmployed())->toBeTrue();

    expect(fn () => resolve(EmployAction::class)->handle($referee))
        ->toThrow(CannotBeEmployedException::class);
});

test('it rejects employing a retired referee without changing retirement', function () {
    $referee = Referee::factory()->retired()->create();
    $retirement = $referee->currentRetirement()->firstOrFail();

    expect($referee->isRetired())->toBeTrue();
    expect($referee->isEmployed())->toBeFalse();

    expect(fn () => resolve(EmployAction::class)->handle($referee))
        ->toThrow(CannotBeEmployedException::class);

    $referee->refresh();
    $retirement->refresh();

    expect($referee->isEmployed())->toBeFalse()
        ->and($referee->isRetired())->toBeTrue()
        ->and($retirement->ended_at)->toBeNull();

    $this->assertDatabaseMissing('employments', [
        'employable_id' => $referee->id,
        'ended_at' => null,
    ]);
});

test('it uses the provided date', function () {
    $referee = Referee::factory()->create();
    $employmentDate = now()->subDays(10);

    resolve(EmployAction::class)->handle($referee, $employmentDate);

    $referee->refresh();
    expect($referee->isEmployed())->toBeTrue();

    // The provided employment date should be persisted
    $this->assertDatabaseHas('employments', [
        'employable_id' => $referee->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it prevents re-employing suspended referee without changing records', function () {
    $referee = Referee::factory()->suspended()->create();
    $suspension = $referee->currentSuspension()->firstOrFail();

    expect(fn () => resolve(EmployAction::class)->handle($referee))
        ->toThrow(CannotBeEmployedException::class);

    $referee->refresh();
    $suspension->refresh();

    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isSuspended())->toBeTrue();
    expect($suspension->ended_at)->toBeNull();
});

test('it validates referee can be employed', function () {
    $referee = Referee::factory()->create();

    // Should succeed without throwing validation exception
    resolve(EmployAction::class)->handle($referee);

    $referee->refresh();
    expect($referee->isEmployed())->toBeTrue();
});

test('it prevents double employment', function () {
    $referee = Referee::factory()->employed()->create();
    $originalEmployment = $referee->currentEmployment()->firstOrFail();

    expect($referee->isEmployed())->toBeTrue();

    expect(fn () => resolve(EmployAction::class)->handle($referee))
        ->toThrow(CannotBeEmployedException::class);

    $referee->refresh();
    expect($referee->isEmployed())->toBeTrue();
    expect($referee->employments()->count())->toBe(1);
    expect($referee->currentEmployment()->firstOrFail()->id)->toBe($originalEmployment->id);
});

test('it updates referee status to employed', function () {
    $referee = Referee::factory()->create();

    expect($referee->status)->not->toBe(EmploymentStatus::Employed);

    resolve(EmployAction::class)->handle($referee);

    $referee->refresh();
    expect($referee->status)->toBe(EmploymentStatus::Employed);
});

test('it creates employment record with correct structure', function () {
    $referee = Referee::factory()->create();
    $employmentDate = now()->subDays(7);

    resolve(EmployAction::class)->handle($referee, $employmentDate);

    $employment = freshModel($referee)->currentEmployment()->firstOrFail();

    expect($employment)->not->toBeNull();
    expect($employment->employable_id)->toBe($referee->id);
    expect(requiredDate($employment->started_at)->toDateTimeString())->toBe($employmentDate->toDateTimeString());
    expect($employment->ended_at)->toBeNull();
});
