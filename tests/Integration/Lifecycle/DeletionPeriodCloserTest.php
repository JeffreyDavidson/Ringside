<?php

declare(strict_types=1);

use App\Lifecycle\DeletionPeriodCloser;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\DB;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it closes every active lifecycle period at the deletion date', function () {
    $wrestler = Wrestler::factory()->create();
    $startedAt = now()->subMonth();
    $deletionDate = now()->subDay();

    $employment = $wrestler->employments()->create(['started_at' => $startedAt]);
    $retirement = $wrestler->retirements()->create(['started_at' => $startedAt]);
    $suspension = $wrestler->suspensions()->create(['started_at' => $startedAt]);
    $injury = $wrestler->injuries()->create(['started_at' => $startedAt]);

    resolve(DeletionPeriodCloser::class)
        ->close($wrestler, $deletionDate);

    expect($employment->refresh()->ended_at?->toDateTimeString())->toBe($deletionDate->toDateTimeString())
        ->and($retirement->refresh()->ended_at?->toDateTimeString())->toBe($deletionDate->toDateTimeString())
        ->and($suspension->refresh()->ended_at?->toDateTimeString())->toBe($deletionDate->toDateTimeString())
        ->and($injury->refresh()->ended_at?->toDateTimeString())->toBe($deletionDate->toDateTimeString());
});

test('it leaves historical lifecycle periods unchanged', function () {
    $wrestler = Wrestler::factory()->create();
    $historicalEnd = now()->subWeek();

    $employment = $wrestler->employments()->create([
        'started_at' => now()->subMonth(),
        'ended_at' => $historicalEnd,
    ]);

    resolve(DeletionPeriodCloser::class)
        ->close($wrestler, now());

    expect($employment->refresh()->ended_at?->toDateTimeString())->toBe($historicalEnd->toDateTimeString());
});

test('it participates in the coordinating transaction', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $employment = $wrestler->currentEmployment()->firstOrFail();

    expect(fn () => DB::transaction(function () use ($wrestler): void {
        resolve(DeletionPeriodCloser::class)
            ->close($wrestler, now());

        throw new RuntimeException('Force rollback.');
    }))->toThrow(RuntimeException::class);

    expect($employment->refresh()->ended_at)->toBeNull();
});
