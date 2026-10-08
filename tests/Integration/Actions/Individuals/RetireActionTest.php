<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Models\Lifecycle\LifecycleTransition;

/*
 * Shared rules for the wrestler, manager and referee RetireAction. They all
 * delegate to IndividualRetirementEligibility, CareerPeriodCloser and
 * RetirementPeriodManager. Behavior that only one type has (relationship
 * cascades) lives beside that type's action tests.
 */
describe('retiring an individual', function () {
    test('it starts a retirement period, ends the employment and records the transition', function (string $modelClass, string $actionClass, bool $withRetirementDate) {
        // Arrange
        $individual = $modelClass::factory()->employed()->create();
        $employment = $individual->currentEmployment()->firstOrFail();
        $retirementDate = $withRetirementDate ? now()->startOfDay() : now();

        // Act
        resolve($actionClass)->handle($individual, $withRetirementDate ? $retirementDate : null);

        // Assert
        $retirement = $individual->refresh()->currentRetirement()->firstOrFail();
        $transition = LifecycleTransition::query()
            ->where('subject_type', $individual->getMorphClass())
            ->where('subject_id', $individual->id)
            ->where('transition', LifecycleTransitionType::Retired)
            ->firstOrFail();

        expect($individual->status)->toBe(EmploymentStatus::Retired)
            ->and($individual->currentEmployment()->exists())->toBeFalse()
            ->and(requiredDate($retirement->started_at)->toDateTimeString())->toBe($retirementDate->toDateTimeString())
            ->and($retirement->ended_at)->toBeNull()
            ->and(requiredDate($employment->refresh()->ended_at)->toDateTimeString())->toBe($retirementDate->toDateTimeString())
            ->and($transition->dimension)->toBe(LifecycleDimension::Retirement)
            ->and($transition->effective_at->toDateTimeString())->toBe($retirementDate->toDateTimeString());
    })->with('individual retire actions')->with([
        'default date' => false,
        'given date' => true,
    ]);

    test('it ends an open suspension or injury together with the employment', function (string $modelClass, string $actionClass, string $state, string $relation, bool $withRetirementDate) {
        // Arrange
        $individual = $modelClass::factory()->{$state}()->create();
        $period = $individual->{$relation}()->firstOrFail();
        $retirementDate = $withRetirementDate ? now()->startOfDay() : now();

        // Act
        resolve($actionClass)->handle($individual, $withRetirementDate ? $retirementDate : null);

        // Assert
        expect($individual->refresh()->status)->toBe(EmploymentStatus::Retired)
            ->and($individual->currentRetirement()->exists())->toBeTrue()
            ->and($individual->{$relation}()->exists())->toBeFalse()
            ->and($individual->currentEmployment()->exists())->toBeFalse()
            ->and(requiredDate($period->refresh()->ended_at)->toDateTimeString())->toBe($retirementDate->toDateTimeString());
    })->with('individual retire actions')->with([
        'suspended' => ['suspended', 'currentSuspension'],
        'injured' => ['injured', 'currentInjury'],
    ])->with([
        'default date' => false,
        'given date' => true,
    ]);

    test('it keeps earlier retirements when the individual retires again', function (string $modelClass, string $actionClass) {
        // Arrange
        $individual = $modelClass::factory()->create();
        $earlierRetirement = $individual->retirements()->create([
            'started_at' => now()->subDays(60),
            'ended_at' => now()->subDays(30),
        ]);
        $individual->employments()->create([
            'started_at' => now()->subDays(25),
            'ended_at' => null,
        ]);

        // Act
        resolve($actionClass)->handle($individual);

        // Assert
        $currentRetirement = $individual->refresh()->currentRetirement()->firstOrFail();

        expect($individual->retirements()->count())->toBe(2)
            ->and($currentRetirement->id)->not->toBe($earlierRetirement->id)
            ->and(requiredDate($currentRetirement->started_at)->toDateTimeString())->toBe(now()->toDateTimeString())
            ->and($earlierRetirement->refresh()->started_at?->toDateTimeString())->toBe(now()->subDays(60)->toDateTimeString())
            ->and($earlierRetirement->ended_at?->toDateTimeString())->toBe(now()->subDays(30)->toDateTimeString());
    })->with('individual retire actions');

    test('it ends employment and an open suspension or injury on their own start dates when retired before they began', function (string $modelClass, string $actionClass, string $relation) {
        // Arrange
        $individual = $modelClass::factory()->create();
        $employmentStartedAt = now()->subDays(10)->startOfSecond();
        $periodStartedAt = now()->subDays(2)->startOfSecond();
        $employment = $individual->employments()->create(['started_at' => $employmentStartedAt]);
        $period = $individual->{$relation}()->create(['started_at' => $periodStartedAt]);

        // Act
        resolve($actionClass)->handle($individual, now()->subDays(20));

        // Assert
        expect($employment->refresh()->ended_at?->toDateTimeString())->toBe($employmentStartedAt->toDateTimeString())
            ->and($period->refresh()->ended_at?->toDateTimeString())->toBe($periodStartedAt->toDateTimeString())
            ->and($individual->currentRetirement()->exists())->toBeTrue();
    })->with('individual retire actions')->with([
        'suspension' => 'suspensions',
        'injury' => 'injuries',
    ]);

    test('it refuses to retire an individual who cannot be retired and leaves the records untouched', function (string $modelClass, string $actionClass, string $state) {
        // Arrange
        $individual = $modelClass::factory()->{$state}()->create();
        $status = $individual->status;
        $retirementCount = $individual->retirements()->count();
        $employmentCount = $individual->employments()->count();
        $endedEmploymentCount = $individual->employments()->whereNotNull('ended_at')->count();
        $transitionCount = LifecycleTransition::query()->count();

        // Act
        $retire = fn () => resolve($actionClass)->handle($individual);

        // Assert
        expect($retire)->toThrow(CannotBeRetiredException::class);

        expect($individual->refresh()->status)->toBe($status)
            ->and($individual->retirements()->count())->toBe($retirementCount)
            ->and($individual->employments()->count())->toBe($employmentCount)
            ->and($individual->employments()->whereNotNull('ended_at')->count())->toBe($endedEmploymentCount)
            ->and(LifecycleTransition::query()->count())->toBe($transitionCount);
    })->with('individual retire actions')->with([
        'unemployed' => 'unemployed',
        'already retired' => 'retired',
        'future employment' => 'withFutureEmployment',
    ]);
});
