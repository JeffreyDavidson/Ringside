<?php

declare(strict_types=1);

use App\Actions\Titles\EndActivityPeriodAction;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Models\Titles\Title;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

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
