<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Models\Lifecycle\LifecycleTransition;

/*
 * Shared rules for the wrestler, manager and referee ReinstateAction. They all
 * delegate to IndividualSuspensionEligibility and SuspensionPeriodManager.
 */
describe('reinstating an individual', function () {
    test('it ends the suspension, keeps the employment and records the transition', function (string $modelClass, string $actionClass, bool $withReinstatementDate) {
        // Arrange
        $individual = $modelClass::factory()->suspended()->create();
        $suspension = $individual->currentSuspension()->firstOrFail();
        $employment = $individual->currentEmployment()->firstOrFail();
        $reinstatementDate = $withReinstatementDate ? now()->startOfDay() : now();

        // Act
        resolve($actionClass)->handle($individual, $withReinstatementDate ? $reinstatementDate : null);

        // Assert
        $transition = LifecycleTransition::query()
            ->where('subject_type', $individual->getMorphClass())
            ->where('subject_id', $individual->id)
            ->where('transition', LifecycleTransitionType::Reinstated)
            ->firstOrFail();

        expect($individual->refresh()->status)->toBe(EmploymentStatus::Employed)
            ->and($individual->currentSuspension()->exists())->toBeFalse()
            ->and($individual->suspensions()->count())->toBe(1)
            ->and(requiredDate($suspension->refresh()->ended_at)->toDateTimeString())->toBe($reinstatementDate->toDateTimeString())
            ->and($individual->currentEmployment()->firstOrFail()->id)->toBe($employment->id)
            ->and($employment->refresh()->ended_at)->toBeNull()
            ->and($individual->currentInjury()->exists())->toBeFalse()
            ->and($individual->currentRetirement()->exists())->toBeFalse()
            ->and($transition->dimension)->toBe(LifecycleDimension::Suspension)
            ->and($transition->effective_at->toDateTimeString())->toBe($reinstatementDate->toDateTimeString());
    })->with('individual reinstate actions')->with([
        'default date' => false,
        'given date' => true,
    ]);

    test('it ends only the current suspension and keeps the earlier history', function (string $modelClass, string $actionClass) {
        // Arrange
        $individual = $modelClass::factory()->employed()->create();
        $earlierSuspension = $individual->suspensions()->create([
            'started_at' => now()->subDays(60),
            'ended_at' => now()->subDays(40),
        ]);
        $currentSuspension = $individual->suspensions()->create([
            'started_at' => now()->subDays(20),
            'ended_at' => null,
        ]);

        // Act
        resolve($actionClass)->handle($individual);

        // Assert
        expect($individual->refresh()->currentSuspension()->exists())->toBeFalse()
            ->and($individual->suspensions()->count())->toBe(2)
            ->and($individual->suspensions()->whereNull('ended_at')->count())->toBe(0)
            ->and($currentSuspension->refresh()->ended_at?->toDateTimeString())->toBe(now()->toDateTimeString())
            ->and($earlierSuspension->refresh()->started_at?->toDateTimeString())->toBe(now()->subDays(60)->toDateTimeString())
            ->and($earlierSuspension->ended_at?->toDateTimeString())->toBe(now()->subDays(40)->toDateTimeString());
    })->with('individual reinstate actions');

    test('it refuses a reinstatement date before the suspension began and leaves it open', function (string $modelClass, string $actionClass) {
        // Arrange
        $individual = $modelClass::factory()->suspended()->create();
        $suspension = $individual->currentSuspension()->firstOrFail();
        $transitionCount = LifecycleTransition::query()->count();

        // Act
        $reinstate = fn () => resolve($actionClass)->handle($individual, now()->subDays(10));

        // Assert
        expect($reinstate)->toThrow(InvalidDateRangeException::class);

        expect($suspension->refresh()->ended_at)->toBeNull()
            ->and($individual->currentSuspension()->exists())->toBeTrue()
            ->and(LifecycleTransition::query()->count())->toBe($transitionCount);
    })->with('individual reinstate actions');

    test('it refuses to reinstate an individual who cannot be reinstated and leaves the records untouched', function (string $modelClass, string $actionClass, string $state) {
        // Arrange
        $individual = $modelClass::factory()->{$state}()->create();
        $status = $individual->status;
        $suspensionCount = $individual->suspensions()->count();
        $injuryCount = $individual->injuries()->count();
        $openInjuryCount = $individual->injuries()->whereNull('ended_at')->count();
        $transitionCount = LifecycleTransition::query()->count();

        // Act
        $reinstate = fn () => resolve($actionClass)->handle($individual);

        // Assert
        expect($reinstate)->toThrow(CannotBeReinstatedException::class);

        expect($individual->refresh()->status)->toBe($status)
            ->and($individual->suspensions()->count())->toBe($suspensionCount)
            ->and($individual->injuries()->count())->toBe($injuryCount)
            ->and($individual->injuries()->whereNull('ended_at')->count())->toBe($openInjuryCount)
            ->and(LifecycleTransition::query()->count())->toBe($transitionCount);
    })->with('individual reinstate actions')->with([
        'injured' => 'injured',
        'available' => 'employed',
        'unemployed' => 'unemployed',
        'retired' => 'retired',
    ]);
});
