<?php

declare(strict_types=1);

use App\Actions\Stables\EndActivityPeriodAction;
use App\Actions\Stables\StartActivityPeriodAction;
use App\Exceptions\BusinessRules\InvalidDateRangeException;
use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;

test('it starts an activity period for an inactive stable', function () {
    $stable = Stable::factory()->create();
    $startedAt = now()->subDay();

    $activityPeriod = resolve(StartActivityPeriodAction::class)->handle($stable, $startedAt);

    expect($activityPeriod->stable_id)->toBe($stable->id)
        ->and($activityPeriod->started_at->toDateTimeString())->toBe($startedAt->toDateTimeString())
        ->and($activityPeriod->ended_at)->toBeNull();
});

test('it rejects starting a second open activity period', function () {
    $stable = Stable::factory()->active()->create();
    $originalPeriod = $stable->currentActivityPeriod()->firstOrFail();

    expect(fn () => resolve(StartActivityPeriodAction::class)->handle($stable, now()))
        ->toThrow(LogicException::class, 'already has an open activity period');

    expect($stable->activityPeriods()->whereNull('ended_at')->count())->toBe(1)
        ->and($originalPeriod->refresh()->started_at->toDateTimeString())
        ->toBe($originalPeriod->started_at->toDateTimeString());
});

test('it ends the open activity period', function () {
    $stable = Stable::factory()->active()->create();
    $endedAt = now()->startOfSecond();

    resolve(EndActivityPeriodAction::class)->handle($stable, $endedAt);

    $endedPeriod = $stable->activityPeriods()->latest('id')->firstOrFail();

    expect($stable->currentActivityPeriod()->doesntExist())->toBeTrue()
        ->and($endedPeriod->ended_at)->not->toBeNull()
        ->and($endedPeriod->ended_at)->toEqual($endedAt);
});

test('it rejects ending activity when no period is open', function () {
    $stable = Stable::factory()->create();

    expect(fn () => resolve(EndActivityPeriodAction::class)->handle($stable, now()))
        ->toThrow(LogicException::class, 'does not have an open activity period');
});

test('it rejects an end date before the activity period starts', function () {
    $startedAt = Carbon::parse('2026-08-10 12:00:00');
    $stable = Stable::factory()
        ->hasActivityPeriods(1, ['started_at' => $startedAt])
        ->create();

    expect(fn () => resolve(EndActivityPeriodAction::class)->handle($stable, $startedAt->copy()->subSecond()))
        ->toThrow(InvalidDateRangeException::class);

    expect($stable->currentActivityPeriod()->exists())->toBeTrue();
});

test('it rejects ending activity in the future', function () {
    $stable = Stable::factory()->active()->create();

    expect(fn () => resolve(EndActivityPeriodAction::class)->handle($stable, now()->addDay()))
        ->toThrow(InvalidDateRangeException::class);

    expect($stable->currentActivityPeriod()->exists())->toBeTrue();
});
