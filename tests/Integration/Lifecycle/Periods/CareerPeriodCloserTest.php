<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\CareerPeriodCloser;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('release ends employment as a released transition and closes the suspension', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $date = now()->subDay()->startOfSecond();
    $employment = $wrestler->employments()->create(['started_at' => now()->subMonth()]);
    $suspension = $wrestler->suspensions()->create(['started_at' => now()->subWeek()]);

    // Act
    resolve(CareerPeriodCloser::class)
        ->release($wrestler, $date);

    // Assert
    expect($employment->refresh()->ended_at?->toDateTimeString())->toBe($date->toDateTimeString())
        ->and($suspension->refresh()->ended_at?->toDateTimeString())->toBe($date->toDateTimeString())
        ->and($wrestler->lifecycleTransitions()->pluck('transition')->all())->toBe([
            LifecycleTransitionType::Released,
        ]);
});

test('release closes an injury when there is no suspension', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $date = now()->subDay()->startOfSecond();
    $wrestler->employments()->create(['started_at' => now()->subMonth()]);
    $injury = $wrestler->injuries()->create(['started_at' => now()->subWeek()]);

    // Act
    resolve(CareerPeriodCloser::class)
        ->release($wrestler, $date);

    // Assert
    expect($injury->refresh()->ended_at?->toDateTimeString())->toBe($date->toDateTimeString());
});

test('release closes only the suspension when a wrestler is both suspended and injured', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $wrestler->employments()->create(['started_at' => now()->subMonth()]);
    $suspension = $wrestler->suspensions()->create(['started_at' => now()->subWeek()]);
    $injury = $wrestler->injuries()->create(['started_at' => now()->subWeek()]);

    // Act
    resolve(CareerPeriodCloser::class)
        ->release($wrestler, now()->subDay());

    // Assert
    expect($suspension->refresh()->ended_at)->not->toBeNull()
        ->and($injury->refresh()->ended_at)->toBeNull();
});

test('release closes a tag team suspension', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $date = now()->subDay()->startOfSecond();
    $employment = $tagTeam->employments()->create(['started_at' => now()->subMonth()]);
    $suspension = $tagTeam->suspensions()->create(['started_at' => now()->subWeek()]);

    // Act
    resolve(CareerPeriodCloser::class)
        ->release($tagTeam, $date);

    // Assert
    expect($employment->refresh()->ended_at?->toDateTimeString())->toBe($date->toDateTimeString())
        ->and($suspension->refresh()->ended_at?->toDateTimeString())->toBe($date->toDateTimeString());
});

test('retire clamps every closed period to its own start date and records no transition', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $employmentStart = now()->subDays(10)->startOfSecond();
    $suspensionStart = now()->subDays(2)->startOfSecond();
    $employment = $wrestler->employments()->create(['started_at' => $employmentStart]);
    $suspension = $wrestler->suspensions()->create(['started_at' => $suspensionStart]);

    // Act
    resolve(CareerPeriodCloser::class)
        ->retire($wrestler, now()->subDays(20));

    // Assert
    expect($employment->refresh()->ended_at?->toDateTimeString())->toBe($employmentStart->toDateTimeString())
        ->and($suspension->refresh()->ended_at?->toDateTimeString())->toBe($suspensionStart->toDateTimeString())
        ->and($wrestler->lifecycleTransitions()->count())->toBe(0);
});

test('retire closes an injury and skips employment that is not open', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $date = now()->subDay()->startOfSecond();
    $injury = $wrestler->injuries()->create(['started_at' => now()->subWeek()]);

    // Act
    resolve(CareerPeriodCloser::class)
        ->retire($wrestler, $date);

    // Assert
    expect($injury->refresh()->ended_at?->toDateTimeString())->toBe($date->toDateTimeString())
        ->and($wrestler->employments()->count())->toBe(0);
});

test('retire closes tag team employment and suspension', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $date = now()->subDay()->startOfSecond();
    $employment = $tagTeam->employments()->create(['started_at' => now()->subMonth()]);
    $suspension = $tagTeam->suspensions()->create(['started_at' => now()->subWeek()]);

    // Act
    resolve(CareerPeriodCloser::class)
        ->retire($tagTeam, $date);

    // Assert
    expect($employment->refresh()->ended_at?->toDateTimeString())->toBe($date->toDateTimeString())
        ->and($suspension->refresh()->ended_at?->toDateTimeString())->toBe($date->toDateTimeString());
});
