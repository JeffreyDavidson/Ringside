<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Models\Lifecycle\LifecycleTransition;

/*
 * Shared rules for the wrestler, manager and referee ClearFromInjuryAction.
 * They all delegate to IndividualInjuryEligibility and InjuryPeriodManager.
 */
describe('clearing an individual from injury', function () {
    test('it ends the injury period, keeps the employment and records the transition', function (string $modelClass, string $actionClass, bool $withRecoveryDate) {
        // Arrange
        $individual = $modelClass::factory()->injured()->create();
        $injury = $individual->currentInjury()->firstOrFail();
        $employment = $individual->currentEmployment()->firstOrFail();
        $recoveryDate = $withRecoveryDate ? now()->startOfDay() : now();

        // Act
        resolve($actionClass)->handle($individual, $withRecoveryDate ? $recoveryDate : null);

        // Assert
        $transition = LifecycleTransition::query()
            ->where('subject_type', $individual->getMorphClass())
            ->where('subject_id', $individual->id)
            ->where('transition', LifecycleTransitionType::ClearedFromInjury)
            ->firstOrFail();

        expect($individual->refresh()->status)->toBe(EmploymentStatus::Employed)
            ->and($individual->currentInjury()->exists())->toBeFalse()
            ->and($individual->injuries()->count())->toBe(1)
            ->and(requiredDate($injury->refresh()->ended_at)->toDateTimeString())->toBe($recoveryDate->toDateTimeString())
            ->and($individual->currentEmployment()->firstOrFail()->id)->toBe($employment->id)
            ->and($employment->refresh()->ended_at)->toBeNull()
            ->and($transition->dimension)->toBe(LifecycleDimension::Injury)
            ->and($transition->effective_at->toDateTimeString())->toBe($recoveryDate->toDateTimeString());
    })->with('individual clear from injury actions')->with([
        'default date' => false,
        'given date' => true,
    ]);

    test('it ends only the current injury and keeps the earlier history', function (string $modelClass, string $actionClass) {
        // Arrange
        $individual = $modelClass::factory()->employed()->create();
        $earlierInjury = $individual->injuries()->create([
            'started_at' => now()->subDays(30),
            'ended_at' => now()->subDays(20),
        ]);
        $currentInjury = $individual->injuries()->create([
            'started_at' => now()->subDays(10),
            'ended_at' => null,
        ]);

        // Act
        resolve($actionClass)->handle($individual);

        // Assert
        expect($individual->refresh()->currentInjury()->exists())->toBeFalse()
            ->and($individual->injuries()->count())->toBe(2)
            ->and($currentInjury->refresh()->started_at?->toDateTimeString())->toBe(now()->subDays(10)->toDateTimeString())
            ->and($currentInjury->ended_at?->toDateTimeString())->toBe(now()->toDateTimeString())
            ->and($earlierInjury->refresh()->started_at?->toDateTimeString())->toBe(now()->subDays(30)->toDateTimeString())
            ->and($earlierInjury->ended_at?->toDateTimeString())->toBe(now()->subDays(20)->toDateTimeString());
    })->with('individual clear from injury actions');

    test('it refuses to clear an individual who is not injured and leaves the records untouched', function (string $modelClass, string $actionClass, string $state) {
        // Arrange
        $individual = $modelClass::factory()->{$state}()->create();
        $status = $individual->status;
        $injuryCount = $individual->injuries()->count();
        $employmentCount = $individual->employments()->count();
        $transitionCount = LifecycleTransition::query()->count();

        // Act
        $clear = fn () => resolve($actionClass)->handle($individual);

        // Assert
        expect($clear)->toThrow(CannotBeClearedFromInjuryException::class);

        expect($individual->refresh()->status)->toBe($status)
            ->and($individual->injuries()->count())->toBe($injuryCount)
            ->and($individual->employments()->count())->toBe($employmentCount)
            ->and(LifecycleTransition::query()->count())->toBe($transitionCount);
    })->with('individual clear from injury actions')->with([
        'unemployed' => 'unemployed',
        'employed' => 'employed',
        'suspended' => 'suspended',
        'retired' => 'retired',
    ]);
});
