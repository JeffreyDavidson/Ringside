<?php

declare(strict_types=1);

use App\Actions\Lifecycle\EndActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Models\Titles\Title;

test('it ends the current title activity period', function () {
    $title = Title::factory()->active()->create();
    $period = $title->currentActivityPeriod()->firstOrFail();

    resolve(EndActivityPeriodAction::class)->handle($title, now());

    expect($period->fresh()?->ended_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('it rejects ending a title without a current activity period', function () {
    $title = Title::factory()->inactive()->create();

    expect(fn () => resolve(EndActivityPeriodAction::class)->handle($title, now()))
        ->toThrow(LogicException::class);
});

test('it rejects an end before the title activity period starts', function () {
    $title = Title::factory()->active()->create();
    $period = $title->currentActivityPeriod()->firstOrFail();

    expect(fn () => resolve(EndActivityPeriodAction::class)->handle(
        $title,
        $period->started_at->copy()->subSecond(),
    ))->toThrow(InvalidDateRangeException::class);
});

test('it rejects a future title activity end', function () {
    $title = Title::factory()->active()->create();

    expect(fn () => resolve(EndActivityPeriodAction::class)->handle($title, now()->addDay()))
        ->toThrow(InvalidDateRangeException::class);
});

test('it records the given transition with its context when ending a period', function () {
    $title = Title::factory()->active()->create();

    resolve(EndActivityPeriodAction::class)->handle(
        $title,
        now(),
        LifecycleTransitionType::Pulled,
        ['notes' => 'Vacated'],
    );

    $transition = $title->lifecycleTransitions()->sole();

    expect($transition->dimension)->toBe(LifecycleDimension::Activity)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Pulled)
        ->and($transition->effective_at->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($transition->context)->toBe(['notes' => 'Vacated']);
});

test('it records no transition when none is given or when the end is rejected', function () {
    $title = Title::factory()->active()->create();
    $period = $title->currentActivityPeriod()->firstOrFail();

    expect(fn () => resolve(EndActivityPeriodAction::class)->handle(
        $title,
        $period->started_at->copy()->subSecond(),
        LifecycleTransitionType::Pulled,
    ))->toThrow(InvalidDateRangeException::class);

    resolve(EndActivityPeriodAction::class)->handle($title, now());

    expect($title->lifecycleTransitions()->exists())->toBeFalse();
});
