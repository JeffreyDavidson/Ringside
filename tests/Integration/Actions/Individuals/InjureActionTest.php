<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Models\Lifecycle\LifecycleTransition;

/*
 * Shared rules for the wrestler, manager and referee InjureAction. They all
 * delegate to IndividualInjuryEligibility and InjuryPeriodManager.
 */
describe('injuring an individual', function () {
    test('it starts an injury period, keeps the employment and records the transition', function (string $modelClass, string $actionClass, bool $withInjuryDate) {
        // Arrange
        $individual = $modelClass::factory()->employed()->create();
        $employment = $individual->currentEmployment()->firstOrFail();
        $injuryDate = $withInjuryDate ? now()->startOfDay() : now();

        // Act
        resolve($actionClass)->handle($individual, $withInjuryDate ? $injuryDate : null);

        // Assert
        $injury = $individual->refresh()->currentInjury()->firstOrFail();
        $transition = LifecycleTransition::query()
            ->where('subject_type', $individual->getMorphClass())
            ->where('subject_id', $individual->id)
            ->where('transition', LifecycleTransitionType::Injured)
            ->firstOrFail();

        expect($individual->status)->toBe(EmploymentStatus::Employed)
            ->and($individual->injuries()->count())->toBe(1)
            ->and($injury->injurable_id)->toBe($individual->id)
            ->and($injury->injurable_type)->toBe($individual->getMorphClass())
            ->and(requiredDate($injury->started_at)->toDateTimeString())->toBe($injuryDate->toDateTimeString())
            ->and($injury->ended_at)->toBeNull()
            ->and($individual->currentEmployment()->firstOrFail()->id)->toBe($employment->id)
            ->and($employment->refresh()->ended_at)->toBeNull()
            ->and($transition->dimension)->toBe(LifecycleDimension::Injury)
            ->and($transition->effective_at->toDateTimeString())->toBe($injuryDate->toDateTimeString());
    })->with('individual injure actions')->with([
        'default date' => false,
        'given date' => true,
    ]);

    test('it keeps earlier injuries when the individual is injured again', function (string $modelClass, string $actionClass) {
        // Arrange
        $individual = $modelClass::factory()->employed()->create();
        $firstInjury = $individual->injuries()->create([
            'started_at' => now()->subDays(100),
            'ended_at' => now()->subDays(60),
        ]);
        $secondInjury = $individual->injuries()->create([
            'started_at' => now()->subDays(50),
            'ended_at' => now()->subDays(20),
        ]);

        // Act
        resolve($actionClass)->handle($individual);

        // Assert
        $currentInjury = $individual->refresh()->currentInjury()->firstOrFail();

        expect($individual->injuries()->count())->toBe(3)
            ->and($currentInjury->id)->not->toBeIn([$firstInjury->id, $secondInjury->id])
            ->and(requiredDate($currentInjury->started_at)->toDateTimeString())->toBe(now()->toDateTimeString())
            ->and($firstInjury->refresh()->started_at?->toDateTimeString())->toBe(now()->subDays(100)->toDateTimeString())
            ->and($firstInjury->ended_at?->toDateTimeString())->toBe(now()->subDays(60)->toDateTimeString())
            ->and($secondInjury->refresh()->started_at?->toDateTimeString())->toBe(now()->subDays(50)->toDateTimeString())
            ->and($secondInjury->ended_at?->toDateTimeString())->toBe(now()->subDays(20)->toDateTimeString());
    })->with('individual injure actions');

    test('it refuses to injure an individual who cannot be injured and leaves the records untouched', function (string $modelClass, string $actionClass, string $state) {
        // Arrange
        $individual = $modelClass::factory()->{$state}()->create();
        $status = $individual->status;
        $injuryCount = $individual->injuries()->count();
        $openInjuryCount = $individual->injuries()->whereNull('ended_at')->count();
        $suspensionCount = $individual->suspensions()->count();
        $employmentCount = $individual->employments()->count();
        $transitionCount = LifecycleTransition::query()->count();

        // Act
        $injure = fn () => resolve($actionClass)->handle($individual);

        // Assert
        expect($injure)->toThrow(CannotBeInjuredException::class);

        expect($individual->refresh()->status)->toBe($status)
            ->and($individual->injuries()->count())->toBe($injuryCount)
            ->and($individual->injuries()->whereNull('ended_at')->count())->toBe($openInjuryCount)
            ->and($individual->suspensions()->count())->toBe($suspensionCount)
            ->and($individual->employments()->count())->toBe($employmentCount)
            ->and(LifecycleTransition::query()->count())->toBe($transitionCount);
    })->with('individual injure actions')->with([
        'unemployed' => 'unemployed',
        'released' => 'released',
        'retired' => 'retired',
        'future employment' => 'withFutureEmployment',
        'suspended' => 'suspended',
        'already injured' => 'injured',
    ]);
});
