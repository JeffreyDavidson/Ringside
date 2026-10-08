<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Individuals\CannotBeSuspendedException;
use App\Models\Lifecycle\LifecycleTransition;

/*
 * Shared rules for the wrestler, manager and referee SuspendAction. They all
 * delegate to IndividualSuspensionEligibility and SuspensionPeriodManager.
 */
describe('suspending an individual', function () {
    test('it starts a suspension period, keeps the employment and records the transition', function (string $modelClass, string $actionClass, bool $withSuspensionDate) {
        // Arrange
        $individual = $modelClass::factory()->employed()->create();
        $employment = $individual->currentEmployment()->firstOrFail();
        $suspensionDate = $withSuspensionDate ? now()->subDays(3) : now();

        // Act
        resolve($actionClass)->handle($individual, $withSuspensionDate ? $suspensionDate : null);

        // Assert
        $suspension = $individual->refresh()->currentSuspension()->firstOrFail();
        $transition = LifecycleTransition::query()
            ->where('subject_type', $individual->getMorphClass())
            ->where('subject_id', $individual->id)
            ->where('transition', LifecycleTransitionType::Suspended)
            ->firstOrFail();

        expect($individual->status)->toBe(EmploymentStatus::Employed)
            ->and($individual->suspensions()->count())->toBe(1)
            ->and($suspension->suspendable->is($individual))->toBeTrue()
            ->and(requiredDate($suspension->started_at)->toDateTimeString())->toBe($suspensionDate->toDateTimeString())
            ->and($suspension->ended_at)->toBeNull()
            ->and($individual->currentEmployment()->firstOrFail()->id)->toBe($employment->id)
            ->and($employment->refresh()->ended_at)->toBeNull()
            ->and($transition->dimension)->toBe(LifecycleDimension::Suspension)
            ->and($transition->effective_at->toDateTimeString())->toBe($suspensionDate->toDateTimeString());
    })->with('individual suspend actions')->with([
        'default date' => false,
        'given date' => true,
    ]);

    test('it keeps earlier suspensions when the individual is suspended again', function (string $modelClass, string $actionClass) {
        // Arrange
        $individual = $modelClass::factory()->create();
        $earlierSuspension = $individual->suspensions()->create([
            'started_at' => now()->subDays(30),
            'ended_at' => now()->subDays(20),
        ]);
        $individual->employments()->create([
            'started_at' => now()->subDays(10),
            'ended_at' => null,
        ]);

        // Act
        resolve($actionClass)->handle($individual);

        // Assert
        $currentSuspension = $individual->refresh()->currentSuspension()->firstOrFail();

        expect($individual->suspensions()->count())->toBe(2)
            ->and($currentSuspension->id)->not->toBe($earlierSuspension->id)
            ->and(requiredDate($currentSuspension->started_at)->toDateTimeString())->toBe(now()->toDateTimeString())
            ->and($earlierSuspension->refresh()->started_at?->toDateTimeString())->toBe(now()->subDays(30)->toDateTimeString())
            ->and($earlierSuspension->ended_at?->toDateTimeString())->toBe(now()->subDays(20)->toDateTimeString());
    })->with('individual suspend actions');

    test('it refuses to suspend an individual who cannot be suspended and leaves the records untouched', function (string $modelClass, string $actionClass, string $state) {
        // Arrange
        $individual = $modelClass::factory()->{$state}()->create();
        $status = $individual->status;
        $suspensionCount = $individual->suspensions()->count();
        $injuryCount = $individual->injuries()->count();
        $employmentCount = $individual->employments()->count();
        $endedEmploymentCount = $individual->employments()->whereNotNull('ended_at')->count();
        $transitionCount = LifecycleTransition::query()->count();

        // Act
        $suspend = fn () => resolve($actionClass)->handle($individual);

        // Assert
        expect($suspend)->toThrow(CannotBeSuspendedException::class);

        expect($individual->refresh()->status)->toBe($status)
            ->and($individual->suspensions()->count())->toBe($suspensionCount)
            ->and($individual->injuries()->count())->toBe($injuryCount)
            ->and($individual->employments()->count())->toBe($employmentCount)
            ->and($individual->employments()->whereNotNull('ended_at')->count())->toBe($endedEmploymentCount)
            ->and(LifecycleTransition::query()->count())->toBe($transitionCount);
    })->with('individual suspend actions')->with([
        'unemployed' => 'unemployed',
        'released' => 'released',
        'retired' => 'retired',
        'future employment' => 'withFutureEmployment',
        'injured' => 'injured',
        'already suspended' => 'suspended',
    ]);
});
