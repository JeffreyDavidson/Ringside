<?php

declare(strict_types=1);

use App\Actions\Lifecycle\StartActivityPeriodAction;
use App\Models\Titles\Title;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

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
