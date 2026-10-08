<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\LifecycleTransition;

/*
 * Shared rules for the wrestler, manager and referee UnretireAction. They all
 * delegate to IndividualRetirementEligibility, RetirementPeriodManager and
 * EmploymentPeriodManager. Behavior that only one type has (the wrestler
 * employing its managers) lives beside that type's action tests.
 */
describe('unretiring an individual', function () {
    test('it ends the retirement, starts an employment period and records the transition', function (string $modelClass, string $actionClass, bool $withUnretirementDate) {
        // Arrange
        $individual = $modelClass::factory()->retired()->create();
        $retirement = $individual->currentRetirement()->firstOrFail();
        $unretirementDate = $withUnretirementDate ? now()->startOfDay() : now();

        // Act
        resolve($actionClass)->handle($individual, $withUnretirementDate ? $unretirementDate : null);

        // Assert
        $employment = $individual->refresh()->currentEmployment()->firstOrFail();
        $unretired = LifecycleTransition::query()
            ->where('subject_type', $individual->getMorphClass())
            ->where('subject_id', $individual->id)
            ->where('transition', LifecycleTransitionType::Unretired)
            ->firstOrFail();

        expect($individual->status)->toBe(EmploymentStatus::Employed)
            ->and($individual->currentRetirement()->exists())->toBeFalse()
            ->and(requiredDate($retirement->refresh()->ended_at)->toDateTimeString())->toBe($unretirementDate->toDateTimeString())
            ->and($employment->employable_id)->toBe($individual->id)
            ->and(requiredDate($employment->started_at)->toDateTimeString())->toBe($unretirementDate->toDateTimeString())
            ->and($employment->ended_at)->toBeNull()
            ->and($unretired->dimension)->toBe(LifecycleDimension::Retirement)
            ->and($unretired->effective_at->toDateTimeString())->toBe($unretirementDate->toDateTimeString());
    })->with('individual unretire actions')->with([
        'default date' => false,
        'given date' => true,
    ]);

    test('it ends the retirement without employing the individual when told not to', function (string $modelClass, string $actionClass) {
        // Arrange
        $individual = $modelClass::factory()->retired()->create();
        $retirement = $individual->currentRetirement()->firstOrFail();
        $employmentCount = $individual->employments()->count();

        // Act
        resolve($actionClass)->handle($individual, null, false);

        // Assert
        expect($individual->refresh()->currentRetirement()->exists())->toBeFalse()
            ->and($individual->currentEmployment()->exists())->toBeFalse()
            ->and($individual->status)->not->toBe(EmploymentStatus::Employed)
            ->and($individual->employments()->count())->toBe($employmentCount)
            ->and(requiredDate($retirement->refresh()->ended_at)->toDateTimeString())->toBe(now()->toDateTimeString());
    })->with('individual unretire actions with optional employment');

    test('it ends only the current retirement and keeps the earlier history', function (string $modelClass, string $actionClass) {
        // Arrange
        $individual = $modelClass::factory()->create();
        $firstRetirement = $individual->retirements()->create([
            'started_at' => now()->subDays(200),
            'ended_at' => now()->subDays(150),
        ]);
        $secondRetirement = $individual->retirements()->create([
            'started_at' => now()->subDays(100),
            'ended_at' => now()->subDays(50),
        ]);
        $currentRetirement = $individual->retirements()->create([
            'started_at' => now()->subDays(20),
            'ended_at' => null,
        ]);
        $earlierEmployment = $individual->employments()->create([
            'started_at' => now()->subDays(300),
            'ended_at' => now()->subDays(200),
        ]);

        // Act
        resolve($actionClass)->handle($individual);

        // Assert
        expect($individual->retirements()->count())->toBe(3)
            ->and($individual->retirements()->whereNull('ended_at')->count())->toBe(0)
            ->and($firstRetirement->refresh()->ended_at?->toDateTimeString())->toBe(now()->subDays(150)->toDateTimeString())
            ->and($secondRetirement->refresh()->ended_at?->toDateTimeString())->toBe(now()->subDays(50)->toDateTimeString())
            ->and($currentRetirement->refresh()->started_at?->toDateTimeString())->toBe(now()->subDays(20)->toDateTimeString())
            ->and($currentRetirement->ended_at?->toDateTimeString())->toBe(now()->toDateTimeString())
            ->and($individual->employments()->count())->toBe(2)
            ->and($earlierEmployment->refresh()->ended_at?->toDateTimeString())->toBe(now()->subDays(200)->toDateTimeString())
            ->and($individual->currentEmployment()->exists())->toBeTrue();
    })->with('individual unretire actions');

    test('it refuses to unretire an individual who cannot be unretired and leaves the records untouched', function (string $modelClass, string $actionClass, string $state, bool $deleted) {
        // Arrange
        $individual = $modelClass::factory()->{$state}()->create();

        if ($deleted) {
            $individual->delete();
        }

        $retirementCount = $individual->retirements()->count();
        $openRetirementCount = $individual->retirements()->whereNull('ended_at')->count();
        $employmentCount = $individual->employments()->count();
        $transitionCount = LifecycleTransition::query()->count();

        // Act
        $unretire = fn () => resolve($actionClass)->handle($individual);

        // Assert
        expect($unretire)->toThrow(CannotBeUnretiredException::class);

        expect($individual->retirements()->count())->toBe($retirementCount)
            ->and($individual->retirements()->whereNull('ended_at')->count())->toBe($openRetirementCount)
            ->and($individual->employments()->count())->toBe($employmentCount)
            ->and(LifecycleTransition::query()->count())->toBe($transitionCount);
    })->with('individual unretire actions')->with([
        'not retired' => ['employed', false],
        'deleted' => ['retired', true],
    ]);

    test('it rolls back the retirement change when starting the employment fails', function (string $modelClass, string $actionClass) {
        // Arrange
        $individual = $modelClass::factory()->retired()->create();
        $retirement = $individual->currentRetirement()->firstOrFail();

        Employment::creating(function (): void {
            throw new RuntimeException('Employment restoration failed.');
        });

        // Act
        $unretire = fn () => resolve($actionClass)->handle($individual);

        // Assert
        try {
            expect($unretire)->toThrow(RuntimeException::class);
        } finally {
            Employment::flushEventListeners();
        }

        expect($individual->refresh()->currentRetirement()->exists())->toBeTrue()
            ->and($individual->currentEmployment()->exists())->toBeFalse()
            ->and($retirement->refresh()->ended_at)->toBeNull();
    })->with('individual unretire actions');
});
