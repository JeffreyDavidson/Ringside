<?php

declare(strict_types=1);

use App\Actions\Titles\ReinstateAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Titles\CannotBeReinstatedException;
use App\Models\Titles\Title;

test('it reinstates an inactive title', function (): void {
    $title = Title::factory()->inactive()->create();

    resolve(ReinstateAction::class)->handle($title);

    expect($title->refresh()->currentActivityPeriod()->exists())->toBeTrue();
});

test('it refuses to reinstate a title whose debut is only scheduled', function (): void {
    $title = Title::factory()->withFutureDebut()->create();
    $scheduledStart = $title->futureActivityPeriod()->firstOrFail()->started_at;

    $reinstate = fn () => resolve(ReinstateAction::class)->handle($title, now());

    expect($reinstate)->toThrow(CannotBeReinstatedException::class, 'Change the debut date instead')
        ->and($title->activityPeriods()->count())->toBe(1)
        ->and($title->futureActivityPeriod()->firstOrFail()->started_at->toDateTimeString())->toBe($scheduledStart->toDateTimeString())
        ->and($title->lifecycleTransitions()->doesntExist())->toBeTrue();
});

test('it still reinstates a pulled title and records the reinstatement', function (): void {
    $title = Title::factory()->inactive()->create();

    resolve(ReinstateAction::class)->handle($title);

    expect($title->lifecycleTransitions()->sole()->transition)->toBe(LifecycleTransitionType::Reinstated)
        ->and($title->activityPeriods()->count())->toBe(2);
});
