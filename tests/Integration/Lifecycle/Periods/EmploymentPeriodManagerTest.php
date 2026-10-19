<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleOwnerType;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\assertDatabaseHas;

test('it starts an employment period on the effective date', function () {
    $wrestler = Wrestler::factory()->unemployed()->create();
    $effectiveDate = now()->subDay();

    resolve(EmploymentPeriodManager::class)->start(
        $wrestler,
        $effectiveDate,
        LifecycleTransitionType::Employed,
    );

    assertDatabaseHas('employments', [
        'employable_id' => $wrestler->id,
        'employable_type' => $wrestler->getMorphClass(),
        'started_at' => $effectiveDate->toDateTimeString(),
        'ended_at' => null,
    ]);

    $transition = $wrestler->lifecycleTransitions()->sole();
    expect($transition->dimension)->toBe(LifecycleDimension::Employment)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Employed)
        ->and($transition->effective_at->toDateTimeString())->toBe($effectiveDate->toDateTimeString());
});

test('it ends and preserves the active employment period', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $effectiveDate = now()->subHour();
    $employmentId = $wrestler->currentEmployment()->firstOrFail()->id;

    resolve(EmploymentPeriodManager::class)->end(
        $wrestler,
        $effectiveDate,
        LifecycleTransitionType::Released,
    );

    assertDatabaseHas('employments', [
        'id' => $employmentId,
        'employable_id' => $wrestler->id,
        'employable_type' => $wrestler->getMorphClass(),
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);

    $transition = $wrestler->lifecycleTransitions()->sole();
    expect($transition->dimension)->toBe(LifecycleDimension::Employment)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Released)
        ->and($transition->effective_at->toDateTimeString())->toBe($effectiveDate->toDateTimeString());
});

test('it records employment transitions for every employable owner', function (LifecycleOwnerType $ownerType) {
    $owner = match ($ownerType) {
        LifecycleOwnerType::Manager => Manager::factory()->create(),
        LifecycleOwnerType::Referee => Referee::factory()->create(),
        LifecycleOwnerType::TagTeam => TagTeam::factory()->create(),
        LifecycleOwnerType::Wrestler => Wrestler::factory()->create(),
        default => throw new LogicException("{$ownerType->value} does not support employment"),
    };
    $effectiveDate = now()->subDay();

    resolve(EmploymentPeriodManager::class)->start(
        $owner,
        $effectiveDate,
        LifecycleTransitionType::Employed,
    );

    $transition = $owner->lifecycleTransitions()->sole();
    expect($transition->subject->is($owner))->toBeTrue()
        ->and($transition->dimension)->toBe(LifecycleDimension::Employment)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Employed);
})->with([
    LifecycleOwnerType::Manager,
    LifecycleOwnerType::Referee,
    LifecycleOwnerType::TagTeam,
    LifecycleOwnerType::Wrestler,
]);

test('it moves a scheduled employment and its employed transition to the new date', function () {
    // Arrange
    $wrestler = Wrestler::factory()->unemployed()->create();
    $manager = resolve(EmploymentPeriodManager::class);
    $manager->start($wrestler, now()->addDays(10), LifecycleTransitionType::Employed);
    $newDate = now()->addDays(3);

    // Act
    $manager->reschedule($wrestler, $newDate);

    // Assert
    $employment = $wrestler->employments()->sole();
    $transition = $wrestler->lifecycleTransitions()->sole();
    expect($employment->started_at->toDateTimeString())->toBe($newDate->toDateTimeString())
        ->and($employment->ended_at)->toBeNull()
        ->and($transition->effective_at->toDateTimeString())->toBe($newDate->toDateTimeString());
});

test('it cancels a scheduled employment and removes its employed transition', function () {
    // Arrange
    $wrestler = Wrestler::factory()->unemployed()->create();
    $manager = resolve(EmploymentPeriodManager::class);
    $manager->start($wrestler, now()->addDays(10), LifecycleTransitionType::Employed);

    // Act
    $manager->cancel($wrestler);

    // Assert
    expect($wrestler->employments()->exists())->toBeFalse()
        ->and($wrestler->lifecycleTransitions()->exists())->toBeFalse();
});

test('it refuses to move or cancel an employment that has started', function (string $method) {
    // Arrange
    $wrestler = Wrestler::factory()->employed()->create();
    $startedAt = $wrestler->currentEmployment()->firstOrFail()->started_at;

    // Act
    $attempt = fn () => $method === 'cancel'
        ? resolve(EmploymentPeriodManager::class)->cancel($wrestler)
        : resolve(EmploymentPeriodManager::class)->reschedule($wrestler, now()->addDay());

    // Assert
    expect($attempt)->toThrow(LogicException::class)
        ->and($wrestler->employments()->sole()->started_at->equalTo($startedAt))->toBeTrue();
})->with(['reschedule', 'cancel']);

test('it only offers a scheduled employment that is the sole employment', function () {
    // Arrange
    $sole = Wrestler::factory()->withFutureEmployment()->create();
    $started = Wrestler::factory()->employed()->create();
    $rehired = Wrestler::factory()->released()->withFutureEmployment()->create();

    // Act
    $found = [$sole->soleScheduledEmployment(), $started->soleScheduledEmployment(), $rehired->soleScheduledEmployment()];

    // Assert
    expect($found[0]?->is($sole->employments()->sole()))->toBeTrue()
        ->and($found[1])->toBeNull()
        ->and($found[2])->toBeNull();
});
