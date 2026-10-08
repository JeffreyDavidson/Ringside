<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;

/*
 * Shared rules for the wrestler, manager and referee EmployAction. They all
 * delegate to IndividualEmploymentEligibility and EmploymentPeriodManager.
 * Behavior that only one type has lives beside that type's action tests.
 */
describe('employing an individual', function () {
    test('it starts an employment period now and marks the individual employed', function (string $modelClass, string $actionClass) {
        // Arrange
        $individual = $modelClass::factory()->unemployed()->create();

        // Act
        resolve($actionClass)->handle($individual);

        // Assert
        $employment = $individual->refresh()->currentEmployment()->firstOrFail();

        expect($individual->status)->toBe(EmploymentStatus::Employed)
            ->and($employment->employable_id)->toBe($individual->id)
            ->and(requiredDate($employment->started_at)->toDateTimeString())->toBe(now()->toDateTimeString())
            ->and($employment->ended_at)->toBeNull();
    })->with('individual employ actions');

    test('it starts the employment period on the given date', function (string $modelClass, string $actionClass) {
        // Arrange
        $individual = $modelClass::factory()->unemployed()->create();
        $employmentDate = now()->subDays(30);

        // Act
        resolve($actionClass)->handle($individual, $employmentDate);

        // Assert
        $employment = $individual->refresh()->currentEmployment()->firstOrFail();

        expect($individual->status)->toBe(EmploymentStatus::Employed)
            ->and(requiredDate($employment->started_at)->toDateTimeString())->toBe($employmentDate->toDateTimeString())
            ->and($employment->ended_at)->toBeNull();
    })->with('individual employ actions');

    test('it refuses to employ an individual who cannot be employed and leaves the records untouched', function (string $modelClass, string $actionClass, string $state, string $relation) {
        // Arrange
        $individual = $modelClass::factory()->{$state}()->create();
        $openRecord = $individual->{$relation}()->firstOrFail();
        $employmentCount = $individual->employments()->count();
        $status = $individual->status;

        // Act
        $employ = fn () => resolve($actionClass)->handle($individual);

        // Assert
        expect($employ)->toThrow(CannotBeEmployedException::class);

        expect($individual->refresh()->status)->toBe($status)
            ->and($individual->employments()->count())->toBe($employmentCount)
            ->and($openRecord->refresh()->ended_at)->toBeNull();
    })->with('individual employ actions')->with([
        'already employed' => ['employed', 'currentEmployment'],
        'suspended' => ['suspended', 'currentSuspension'],
        'injured' => ['injured', 'currentInjury'],
        'retired' => ['retired', 'currentRetirement'],
        'future employment' => ['withFutureEmployment', 'futureEmployment'],
    ]);
});
