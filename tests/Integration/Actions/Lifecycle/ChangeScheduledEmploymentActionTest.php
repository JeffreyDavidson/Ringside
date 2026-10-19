<?php

declare(strict_types=1);

use App\Actions\Lifecycle\ChangeScheduledEmploymentAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

/** A record of the given type that has never been employed, in the given promotion. */
function unemployedRecord(string $type, ?Promotion $promotion = null): Wrestler|Manager|Referee|TagTeam
{
    $promotion ??= Promotion::factory()->create();

    return match ($type) {
        'wrestler' => Wrestler::factory()->unemployed()->for($promotion)->createOne(),
        'manager' => Manager::factory()->unemployed()->for($promotion)->createOne(),
        'referee' => Referee::factory()->unemployed()->for($promotion)->createOne(),
        'tag team' => TagTeam::factory()->unemployed()->for($promotion)->createOne(),
        default => throw new LogicException("Unknown employable type {$type}"),
    };
}

/** A record of the given type whose only employment is scheduled in ten days, with its employed transition. */
function recordScheduledTenDaysOut(string $type, ?Promotion $promotion = null): Wrestler|Manager|Referee|TagTeam
{
    $record = unemployedRecord($type, $promotion);

    resolve(EmploymentPeriodManager::class)->start($record, now()->addDays(10)->startOfDay(), LifecycleTransitionType::Employed);

    return $record;
}

$types = ['wrestler', 'manager', 'referee', 'tag team'];

test('it moves a scheduled employment to another future date', function (string $type) {
    // Arrange
    $record = recordScheduledTenDaysOut($type);
    $newDate = now()->addDays(20)->startOfDay();

    // Act
    $handled = resolve(ChangeScheduledEmploymentAction::class)->handle($record, $newDate);

    // Assert
    expect($handled)->toBeTrue()
        ->and($record->employments()->sole()->started_at->toDateTimeString())->toBe($newDate->toDateTimeString())
        ->and($record->lifecycleTransitions()->count())->toBe(2)
        ->and($record->refresh()->hasFutureEmployment())->toBeTrue();
})->with($types);

test('it starts the employment today when moved to today', function (string $type) {
    // Arrange
    $record = recordScheduledTenDaysOut($type);

    // Act
    resolve(ChangeScheduledEmploymentAction::class)->handle($record, now()->startOfDay());

    // Assert
    expect($record->refresh()->hasCurrentEmployment())->toBeTrue()
        ->and($record->hasFutureEmployment())->toBeFalse();
})->with($types);

test('it cancels the scheduled employment when the date is cleared', function (string $type) {
    // Arrange
    $record = recordScheduledTenDaysOut($type);

    // Act
    $handled = resolve(ChangeScheduledEmploymentAction::class)->handle($record, null);

    // Assert
    expect($handled)->toBeTrue()
        ->and($record->employments()->exists())->toBeFalse()
        ->and($record->lifecycleTransitions()->where('transition', LifecycleTransitionType::EmploymentCancelled)->exists())->toBeTrue()
        ->and($record->refresh()->hasEmploymentHistory())->toBeFalse();
})->with($types);

test('it leaves the time of day alone when the day in the promotions time zone is unchanged', function (string $type) {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
    $record = recordScheduledTenDaysOut($type, $promotion);
    $employment = $record->employments()->sole();
    $employment->update(['started_at' => now()->addDays(10)->setTime(15, 30)]);
    $sameLocalDay = now()->addDays(10)->setTime(8, 0);

    // Act
    $handled = resolve(ChangeScheduledEmploymentAction::class)->handle($record, $sameLocalDay);

    // Assert
    expect($handled)->toBeTrue()
        ->and($record->employments()->sole()->started_at->toDateTimeString())->toBe(now()->addDays(10)->setTime(15, 30)->toDateTimeString());
})->with($types);

test('it does nothing to an employment that has started', function (string $type) {
    // Arrange
    $record = match ($type) {
        'wrestler' => Wrestler::factory()->employed()->createOne(),
        'manager' => Manager::factory()->employed()->createOne(),
        'referee' => Referee::factory()->employed()->createOne(),
        'tag team' => TagTeam::factory()->employed()->createOne(),
        default => throw new LogicException("Unknown employable type {$type}"),
    };
    $startedAt = $record->employments()->sole()->started_at->toDateTimeString();

    // Act
    $moved = resolve(ChangeScheduledEmploymentAction::class)->handle($record, now()->addDays(20));
    $cleared = resolve(ChangeScheduledEmploymentAction::class)->handle($record, null);

    // Assert
    expect($moved)->toBeFalse()
        ->and($cleared)->toBeFalse()
        ->and($record->employments()->sole()->started_at->toDateTimeString())->toBe($startedAt);
})->with($types);

test('it does nothing when the scheduled employment follows an earlier one', function () {
    // Arrange
    $wrestler = Wrestler::factory()->released()->withFutureEmployment()->create();
    $scheduledFor = $wrestler->futureEmployment()->firstOrFail()->started_at->toDateTimeString();

    // Act
    $handled = resolve(ChangeScheduledEmploymentAction::class)->handle($wrestler, null);

    // Assert
    expect($handled)->toBeFalse()
        ->and($wrestler->futureEmployment()->firstOrFail()->started_at->toDateTimeString())->toBe($scheduledFor);
});
