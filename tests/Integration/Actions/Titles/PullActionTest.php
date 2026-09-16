<?php

declare(strict_types=1);

use App\Actions\Titles\PullAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Titles\CannotBePulledException;
use App\Models\Titles\Title;

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
