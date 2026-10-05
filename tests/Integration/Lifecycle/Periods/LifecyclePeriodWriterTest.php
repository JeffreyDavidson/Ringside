<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Periods\InjuryPeriodManager;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Periods\SuspensionPeriodManager;
use App\Models\Roster\Wrestlers\Wrestler;

dataset('period managers', [
    'employment' => [EmploymentPeriodManager::class, 'employments', LifecycleTransitionType::Released],
    'injury' => [InjuryPeriodManager::class, 'injuries', LifecycleTransitionType::ClearedFromInjury],
    'suspension' => [SuspensionPeriodManager::class, 'suspensions', LifecycleTransitionType::Reinstated],
    'retirement' => [RetirementPeriodManager::class, 'retirements', LifecycleTransitionType::Unretired],
]);

// An employment only counts as current once it has started, so a future start applies to the other periods.
dataset('periods that may start in the future', [
    'injury' => [InjuryPeriodManager::class, 'injuries', LifecycleTransitionType::ClearedFromInjury],
    'suspension' => [SuspensionPeriodManager::class, 'suspensions', LifecycleTransitionType::Reinstated],
    'retirement' => [RetirementPeriodManager::class, 'retirements', LifecycleTransitionType::Unretired],
]);

test('it rejects an end date before the start of the open period', function (string $managerClass, string $relation, LifecycleTransitionType $transition) {
    $wrestler = Wrestler::factory()->create();
    $period = $wrestler->{$relation}()->create(['started_at' => now()->subDays(10)]);

    expect(fn () => resolve($managerClass)->end($wrestler, now()->subDays(20), $transition))
        ->toThrow(InvalidDateRangeException::class)
        ->and($period->refresh()->ended_at)->toBeNull()
        ->and($wrestler->lifecycleTransitions()->exists())->toBeFalse();
})->with('period managers');

test('it rejects ending a period that starts in the future today', function (string $managerClass, string $relation, LifecycleTransitionType $transition) {
    $wrestler = Wrestler::factory()->create();
    $period = $wrestler->{$relation}()->create(['started_at' => now()->addDays(10)]);

    expect(fn () => resolve($managerClass)->end($wrestler, now(), $transition))
        ->toThrow(InvalidDateRangeException::class)
        ->and($period->refresh()->ended_at)->toBeNull()
        ->and($wrestler->lifecycleTransitions()->exists())->toBeFalse();
})->with('periods that may start in the future');

test('it ends a period on its own start date when the end is clamped', function (string $managerClass, string $relation, LifecycleTransitionType $transition) {
    $wrestler = Wrestler::factory()->create();
    $startedAt = now()->addDays(10)->startOfSecond();
    $period = $wrestler->{$relation}()->create(['started_at' => $startedAt]);

    resolve($managerClass)->end($wrestler, now(), $transition, clampToStart: true);

    expect($period->refresh()->ended_at?->toDateTimeString())->toBe($startedAt->toDateTimeString())
        ->and($wrestler->lifecycleTransitions()->sole()->effective_at->toDateTimeString())->toBe($startedAt->toDateTimeString());
})->with('periods that may start in the future');

test('it clamps an explicit date before the start of an employment', function () {
    $wrestler = Wrestler::factory()->create();
    $startedAt = now()->subDays(10)->startOfSecond();
    $employment = $wrestler->employments()->create(['started_at' => $startedAt]);

    resolve(EmploymentPeriodManager::class)->end($wrestler, now()->subDays(20), clampToStart: true);

    expect($employment->refresh()->ended_at?->toDateTimeString())->toBe($startedAt->toDateTimeString());
});

test('it ends the open period on a date after its start', function (string $managerClass, string $relation, LifecycleTransitionType $transition) {
    $wrestler = Wrestler::factory()->create();
    $endedAt = now()->subDays(2)->startOfSecond();
    $period = $wrestler->{$relation}()->create(['started_at' => now()->subDays(10)]);

    resolve($managerClass)->end($wrestler, $endedAt, $transition);

    expect($period->refresh()->ended_at?->toDateTimeString())->toBe($endedAt->toDateTimeString())
        ->and($wrestler->lifecycleTransitions()->sole()->effective_at->toDateTimeString())->toBe($endedAt->toDateTimeString());
})->with('period managers');
