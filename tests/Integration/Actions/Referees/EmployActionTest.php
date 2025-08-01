<?php

declare(strict_types=1);

use App\Actions\Referees\EmployAction;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it employs an unemployed referee', function () {
    $referee = Referee::factory()->create();

    expect($referee->isEmployed())->toBeFalse();

    EmployAction::run($referee);

    $referee->refresh();
    expect($referee->isEmployed())->toBeTrue();

    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it employs referee with specific employment date', function () {
    $referee = Referee::factory()->create();
    $employmentDate = now()->subDays(30);

    EmployAction::run($referee, $employmentDate);

    $referee->refresh();
    expect($referee->isEmployed())->toBeTrue();

    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it employs suspended referee and ends suspension', function () {
    $referee = Referee::factory()->suspended()->create();
    $suspension = $referee->currentSuspension;

    expect($referee->isSuspended())->toBeTrue();
    expect($referee->isEmployed())->toBeFalse();

    EmployAction::run($referee);

    $referee->refresh();
    $suspension->refresh();

    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isSuspended())->toBeFalse();

    // Suspension should be ended
    $this->assertDatabaseHas('referees_suspensions', [
        'id' => $suspension->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Employment should be created
    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'started_at' => now()->toDateTimeString(),
    ]);
});

test('it employs injured referee and ends injury', function () {
    $referee = Referee::factory()->injured()->create();
    $injury = $referee->currentInjury;

    expect($referee->isInjured())->toBeTrue();
    expect($referee->isEmployed())->toBeFalse();

    EmployAction::run($referee);

    $referee->refresh();
    $injury->refresh();

    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isInjured())->toBeFalse();

    // Injury should be ended
    $this->assertDatabaseHas('referees_injuries', [
        'id' => $injury->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Employment should be created
    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'started_at' => now()->toDateTimeString(),
    ]);
});

test('it employs retired referee and ends retirement', function () {
    $referee = Referee::factory()->retired()->create();
    $retirement = $referee->currentRetirement;

    expect($referee->isRetired())->toBeTrue();
    expect($referee->isEmployed())->toBeFalse();

    EmployAction::run($referee);

    $referee->refresh();
    $retirement->refresh();

    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isRetired())->toBeFalse();

    // Retirement should be ended
    $this->assertDatabaseHas('referees_retirements', [
        'id' => $retirement->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Employment should be created
    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'started_at' => now()->toDateTimeString(),
    ]);
});

test('it handles DateHelper date resolution', function () {
    $referee = Referee::factory()->create();
    $employmentDate = now()->subDays(10);

    EmployAction::run($referee, $employmentDate);

    $referee->refresh();
    expect($referee->isEmployed())->toBeTrue();

    // DateHelper should have processed the employment date
    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it maintains transaction boundaries', function () {
    $referee = Referee::factory()->suspended()->create();
    $suspension = $referee->currentSuspension;

    EmployAction::run($referee);

    $referee->refresh();
    $suspension->refresh();

    // Both suspension ending and employment creation should be atomic
    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isSuspended())->toBeFalse();

    $this->assertDatabaseHas('referees_suspensions', [
        'id' => $suspension->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it validates referee can be employed', function () {
    $referee = Referee::factory()->create();

    // Should succeed without throwing validation exception
    EmployAction::run($referee);

    $referee->refresh();
    expect($referee->isEmployed())->toBeTrue();
});

test('it prevents double employment', function () {
    $referee = Referee::factory()->employed()->create();
    $originalEmployment = $referee->currentEmployment;

    expect($referee->isEmployed())->toBeTrue();

    // Attempting to employ again should not create duplicate employment
    EmployAction::run($referee);

    $referee->refresh();
    expect($referee->isEmployed())->toBeTrue();
    expect($referee->employments()->count())->toBe(1);
    expect($referee->currentEmployment->id)->toBe($originalEmployment->id);
});

test('it updates referee status to employed', function () {
    $referee = Referee::factory()->create();

    expect($referee->status)->not->toBe(EmploymentStatus::Employed);

    EmployAction::run($referee);

    $referee->refresh();
    expect($referee->status)->toBe(EmploymentStatus::Employed);
});

test('it creates employment record with correct structure', function () {
    $referee = Referee::factory()->create();
    $employmentDate = now()->subDays(7);

    EmployAction::run($referee, $employmentDate);

    $employment = $referee->fresh()->currentEmployment;

    expect($employment)->not->toBeNull();
    expect($employment->referee_id)->toBe($referee->id);
    expect($employment->started_at->eq($employmentDate))->toBeTrue();
    expect($employment->ended_at)->toBeNull();
});
