<?php

declare(strict_types=1);

use App\Actions\Lifecycle\StartActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Titles\Title;

test('it starts a title activity period', function () {
    $title = Title::factory()->unactivated()->create();

    $period = resolve(StartActivityPeriodAction::class)->handle($title, now(), rescheduleFuturePeriod: true);

    expect($period->activeable_id)->toBe($title->id)
        ->and($period->activeable_type)->toBe($title->getMorphClass())
        ->and($period->started_at->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($period->ended_at)->toBeNull();
});

test('it rejects a second current title activity period', function () {
    $title = Title::factory()->active()->create();

    expect(fn () => resolve(StartActivityPeriodAction::class)->handle($title, now(), rescheduleFuturePeriod: true))
        ->toThrow(LogicException::class);
});

test('it reschedules a pending title activity period', function () {
    $title = Title::factory()->withFutureActivation()->create();
    $pendingPeriod = $title->futureActivityPeriod()->firstOrFail();

    $period = resolve(StartActivityPeriodAction::class)->handle($title, now(), rescheduleFuturePeriod: true);

    expect($period->is($pendingPeriod))->toBeTrue()
        ->and($period->started_at->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($title->activityPeriods()->count())->toBe(1);
});

test('it records the given transition with its context when starting a period', function () {
    $title = Title::factory()->unactivated()->create();

    resolve(StartActivityPeriodAction::class)->handle(
        $title,
        now(),
        transition: LifecycleTransitionType::Debuted,
        context: ['notes' => 'Opening night'],
    );

    $transition = $title->lifecycleTransitions()->sole();

    expect($transition->dimension)->toBe(LifecycleDimension::Activity)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Debuted)
        ->and($transition->effective_at->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($transition->context)->toBe(['notes' => 'Opening night']);
});

test('it records the given transition when rescheduling a pending period', function () {
    $title = Title::factory()->withFutureActivation()->create();

    resolve(StartActivityPeriodAction::class)->handle(
        $title,
        now(),
        rescheduleFuturePeriod: true,
        transition: LifecycleTransitionType::Reinstated,
    );

    expect($title->lifecycleTransitions()->sole()->transition)->toBe(LifecycleTransitionType::Reinstated);
});

test('it records no transition when none is given', function () {
    $title = Title::factory()->unactivated()->create();

    resolve(StartActivityPeriodAction::class)->handle($title, now());

    expect($title->lifecycleTransitions()->exists())->toBeFalse();
});
