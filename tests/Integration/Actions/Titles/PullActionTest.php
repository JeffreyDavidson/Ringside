<?php

declare(strict_types=1);

use App\Actions\Titles\PullAction;
use App\Actions\Titles\ReinstateAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Titles\CannotBePulledException;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

test('it pulls an active title with its date and notes', function (): void {
    $title = Title::factory()->active()->create();
    $pulledAt = now()->subDay();

    resolve(PullAction::class)->handle($title, $pulledAt, 'Temporarily withdrawn');

    $title->refresh();
    $transition = $title->lifecycleTransitions()->sole();
    $activityPeriod = $title->activityPeriods()->sole();

    expect($title->currentActivityPeriod()->exists())->toBeFalse()
        ->and(requiredDate($activityPeriod->ended_at)->toDateTimeString())->toBe($pulledAt->toDateTimeString())
        ->and($transition->transition)->toBe(LifecycleTransitionType::Pulled)
        ->and($transition->effective_at->toDateTimeString())->toBe($pulledAt->toDateTimeString())
        ->and($transition->context)->toBe(['notes' => 'Temporarily withdrawn']);
});

test('it rejects pulling a title that is not active', function (): void {
    $title = Title::factory()->inactive()->create();

    expect(fn () => resolve(PullAction::class)->handle($title))
        ->toThrow(CannotBePulledException::class);
});

test('pulling a title ends its current championship reign on the pull date', function (): void {
    // Arrange
    $title = Title::factory()->active()->create();
    $championship = TitleChampionship::factory()
        ->for($title)
        ->current()
        ->create();
    $pulledAt = now()->subDay()->startOfSecond();

    // Act
    resolve(PullAction::class)->handle($title, $pulledAt);

    // Assert
    expect($championship->refresh()->lost_at)->toEqual($pulledAt)
        ->and($title->currentChampionship()->exists())->toBeFalse();
});

test('reinstating a pulled title leaves it vacant', function (): void {
    // Arrange
    $title = Title::factory()->active()->create();
    $championship = TitleChampionship::factory()
        ->for($title)
        ->current()
        ->create();
    $pulledAt = now()->subDay()->startOfSecond();
    resolve(PullAction::class)->handle($title, $pulledAt);

    // Act
    resolve(ReinstateAction::class)->handle($title->refresh());

    // Assert
    expect($title->currentChampionship()->exists())->toBeFalse()
        ->and($championship->refresh()->lost_at)->toEqual($pulledAt)
        ->and($title->currentActivityPeriod()->exists())->toBeTrue();
});

test('pulling a vacant title still succeeds', function (): void {
    // Arrange
    $title = Title::factory()->active()->create();

    // Act
    resolve(PullAction::class)->handle($title);

    // Assert
    expect($title->refresh()->currentActivityPeriod()->exists())->toBeFalse()
        ->and($title->championships()->exists())->toBeFalse();
});
