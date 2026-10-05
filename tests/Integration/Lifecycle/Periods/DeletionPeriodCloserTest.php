<?php

declare(strict_types=1);

use App\Lifecycle\Periods\DeletionPeriodCloser;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
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
    }))->toThrow(RuntimeException::class)
        ->and($employment->refresh()->ended_at)->toBeNull();
});

test('it closes periods that start after the deletion date on their own start date', function () {
    $wrestler = Wrestler::factory()->create();
    $startedAt = now()->addDays(10)->startOfSecond();

    $retirement = $wrestler->retirements()->create(['started_at' => $startedAt]);
    $suspension = $wrestler->suspensions()->create(['started_at' => $startedAt]);
    $injury = $wrestler->injuries()->create(['started_at' => $startedAt]);

    resolve(DeletionPeriodCloser::class)
        ->close($wrestler, now());

    expect($retirement->refresh()->ended_at?->toDateTimeString())->toBe($startedAt->toDateTimeString())
        ->and($suspension->refresh()->ended_at?->toDateTimeString())->toBe($startedAt->toDateTimeString())
        ->and($injury->refresh()->ended_at?->toDateTimeString())->toBe($startedAt->toDateTimeString());
});

test('it closes an employment that started after a back-dated deletion on its own start date', function () {
    $wrestler = Wrestler::factory()->create();
    $startedAt = now()->subDays(10)->startOfSecond();
    $employment = $wrestler->employments()->create(['started_at' => $startedAt]);

    resolve(DeletionPeriodCloser::class)
        ->close($wrestler, now()->subDays(20));

    expect($employment->refresh()->ended_at?->toDateTimeString())->toBe($startedAt->toDateTimeString());
});

test('it closes an employment that starts after the deletion date on its own start date', function (string $subjectClass) {
    // Arrange
    testTime()->freeze('2026-03-10 09:00:00');
    $subject = $subjectClass::factory()->create();
    $startedAt = Carbon::parse('2026-03-20 00:00:00');
    $employment = $subject->employments()->create(['started_at' => $startedAt]);

    // Act
    resolve(DeletionPeriodCloser::class)
        ->close($subject, now());

    // Assert
    expect($employment->refresh()->ended_at?->toDateTimeString())->toBe($startedAt->toDateTimeString())
        ->and($subject->employments()->whereNull('ended_at')->exists())->toBeFalse();
})->with([
    'wrestler' => [Wrestler::class],
    'manager' => [Manager::class],
    'referee' => [Referee::class],
]);
