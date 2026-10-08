<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Individuals\CannotBeReleasedException;

/*
 * Shared rules for the wrestler, manager and referee ReleaseAction. They all
 * delegate to IndividualEmploymentEligibility and CareerPeriodCloser.
 * Behavior that only one type has lives beside that type's action tests.
 */
describe('releasing an individual', function () {
    test('it ends the current employment and marks the individual released', function (string $modelClass, string $actionClass, bool $withReleaseDate) {
        // Arrange
        $individual = $modelClass::factory()->employed()->create();
        $employment = $individual->currentEmployment()->firstOrFail();
        $releaseDate = $withReleaseDate ? now()->startOfDay() : now();

        // Act
        resolve($actionClass)->handle($individual, $withReleaseDate ? $releaseDate : null);

        // Assert
        expect($individual->refresh()->status)->toBe(EmploymentStatus::Released)
            ->and($individual->currentEmployment()->exists())->toBeFalse()
            ->and(requiredDate($employment->refresh()->ended_at)->toDateTimeString())->toBe($releaseDate->toDateTimeString());
    })->with('individual release actions')->with([
        'default date' => false,
        'given date' => true,
    ]);

    test('it ends an open suspension or injury together with the employment', function (string $modelClass, string $actionClass, string $state, string $relation, bool $withReleaseDate) {
        // Arrange
        $individual = $modelClass::factory()->{$state}()->create();
        $period = $individual->{$relation}()->firstOrFail();
        $releaseDate = $withReleaseDate ? now()->startOfDay() : now();

        // Act
        resolve($actionClass)->handle($individual, $withReleaseDate ? $releaseDate : null);

        // Assert
        expect($individual->refresh()->status)->toBe(EmploymentStatus::Released)
            ->and($individual->{$relation}()->exists())->toBeFalse()
            ->and($individual->currentEmployment()->exists())->toBeFalse()
            ->and(requiredDate($period->refresh()->ended_at)->toDateTimeString())->toBe($releaseDate->toDateTimeString());
    })->with('individual release actions')->with([
        'suspended' => ['suspended', 'currentSuspension'],
        'injured' => ['injured', 'currentInjury'],
    ])->with([
        'default date' => false,
        'given date' => true,
    ]);

    test('it only ends the current employment and keeps earlier employments', function (string $modelClass, string $actionClass) {
        // Arrange
        $individual = $modelClass::factory()->unemployed()->create();
        $earlierEmployment = $individual->employments()->create([
            'started_at' => now()->subDays(100),
            'ended_at' => now()->subDays(50),
        ]);
        $currentEmployment = $individual->employments()->create([
            'started_at' => now()->subDays(30),
            'ended_at' => null,
        ]);

        // Act
        resolve($actionClass)->handle($individual);

        // Assert
        expect($individual->employments()->count())->toBe(2)
            ->and($earlierEmployment->refresh()->ended_at?->toDateTimeString())->toBe(now()->subDays(50)->toDateTimeString())
            ->and($currentEmployment->refresh()->ended_at?->toDateTimeString())->toBe(now()->toDateTimeString())
            ->and($currentEmployment->started_at?->toDateTimeString())->toBe(now()->subDays(30)->toDateTimeString());
    })->with('individual release actions');

    test('it ends only the open suspension or injury and keeps the earlier ones', function (string $modelClass, string $actionClass, string $relation) {
        // Arrange
        $individual = $modelClass::factory()->employed()->create();
        $earlierPeriod = $individual->{$relation}()->create([
            'started_at' => now()->subDays(5),
            'ended_at' => now()->subDays(3),
        ]);
        $openPeriod = $individual->{$relation}()->create(['started_at' => now()->subDays(2)]);

        // Act
        resolve($actionClass)->handle($individual);

        // Assert
        expect($individual->{$relation}()->count())->toBe(2)
            ->and($earlierPeriod->refresh()->ended_at?->toDateTimeString())->toBe(now()->subDays(3)->toDateTimeString())
            ->and($openPeriod->refresh()->ended_at?->toDateTimeString())->toBe(now()->toDateTimeString());
    })->with('individual release actions')->with([
        'suspensions' => 'suspensions',
        'injuries' => 'injuries',
    ]);

    test('it ends an open suspension or injury on its own start date when released before it began', function (string $modelClass, string $actionClass, string $relation) {
        // Arrange
        $individual = $modelClass::factory()->create();
        $individual->employments()->create(['started_at' => now()->subDays(10)]);
        $startedAt = now()->subDays(2)->startOfSecond();
        $period = $individual->{$relation}()->create(['started_at' => $startedAt]);

        // Act
        resolve($actionClass)->handle($individual, $startedAt->copy()->subSecond());

        // Assert
        expect($period->refresh()->ended_at?->toDateTimeString())->toBe($startedAt->toDateTimeString())
            ->and($individual->currentEmployment()->exists())->toBeFalse();
    })->with('individual release actions')->with([
        'suspension' => 'suspensions',
        'injury' => 'injuries',
    ]);

    test('it refuses to release an individual who cannot be released and leaves the records untouched', function (string $modelClass, string $actionClass, string $state) {
        // Arrange
        $individual = $modelClass::factory()->{$state}()->create();
        $employmentCount = $individual->employments()->count();
        $endedEmploymentCount = $individual->employments()->whereNotNull('ended_at')->count();
        $status = $individual->status;

        // Act
        $release = fn () => resolve($actionClass)->handle($individual);

        // Assert
        expect($release)->toThrow(CannotBeReleasedException::class);

        expect($individual->refresh()->status)->toBe($status)
            ->and($individual->employments()->count())->toBe($employmentCount)
            ->and($individual->employments()->whereNotNull('ended_at')->count())->toBe($endedEmploymentCount);
    })->with('individual release actions')->with([
        'unemployed' => 'unemployed',
        'already released' => 'released',
        'retired' => 'retired',
        'future employment' => 'withFutureEmployment',
    ]);
});
