<?php

declare(strict_types=1);

use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Retirement;
use App\Models\Lifecycle\Suspension;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Database\Factories\Lifecycle\ActivityPeriodFactory;
use Database\Factories\Lifecycle\EmploymentFactory;
use Database\Factories\Lifecycle\InjuryFactory;
use Database\Factories\Lifecycle\RetirementFactory;
use Database\Factories\Lifecycle\SuspensionFactory;
use Database\Factories\Roster\Managers\ManagerFactory;
use Database\Factories\Roster\Referees\RefereeFactory;
use Database\Factories\Roster\Wrestlers\WrestlerFactory;

test('lifecycle period factories create valid date ranges', function (
    ActivityPeriodFactory|EmploymentFactory|InjuryFactory|RetirementFactory|SuspensionFactory $factory,
) {
    $startedAt = now()->subMonth();
    $endedAt = now()->subWeek();

    $period = $factory
        ->started($startedAt)
        ->ended($endedAt)
        ->makeOne();

    expect($period->started_at->toDateTimeString())->toBe($startedAt->toDateTimeString())
        ->and($period->ended_at?->toDateTimeString())->toBe($endedAt->toDateTimeString());
})->with('lifecycle period factories');

test('lifecycle period factories reject an end before the start', function (
    ActivityPeriodFactory|EmploymentFactory|InjuryFactory|RetirementFactory|SuspensionFactory $factory,
) {
    $startedAt = now()->subWeek();
    $endedAt = now()->subMonth();

    expect(fn () => $factory->started($startedAt)->ended($endedAt)->makeOne())
        ->toThrow(InvalidArgumentException::class, 'A lifecycle period cannot end before it starts.');
})->with('lifecycle period factories');

test('current lifecycle period state preserves its configured start', function (
    ActivityPeriodFactory|EmploymentFactory|InjuryFactory|RetirementFactory|SuspensionFactory $factory,
) {
    $startedAt = now()->subMonth();

    $period = $factory
        ->started($startedAt)
        ->ended(now()->subWeek())
        ->current()
        ->makeOne();

    expect($period->started_at->toDateTimeString())->toBe($startedAt->toDateTimeString())
        ->and($period->ended_at)->toBeNull();
})->with('lifecycle period factories');

test('individual lifecycle factory states do not combine injury and suspension', function (
    ManagerFactory|RefereeFactory|WrestlerFactory $factory,
    string $expectedCurrentPeriod,
) {
    $individual = $factory->createOne();

    expect($individual->currentInjury()->exists())->toBe($expectedCurrentPeriod === 'injury')
        ->and($individual->currentSuspension()->exists())->toBe($expectedCurrentPeriod === 'suspension');
})->with([
    'injured manager' => [fn (): ManagerFactory => Manager::factory()->injured(), 'injury'],
    'suspended manager' => [fn (): ManagerFactory => Manager::factory()->suspended(), 'suspension'],
    'injured referee' => [fn (): RefereeFactory => Referee::factory()->injured(), 'injury'],
    'suspended referee' => [fn (): RefereeFactory => Referee::factory()->suspended(), 'suspension'],
    'injured wrestler' => [fn (): WrestlerFactory => Wrestler::factory()->injured(), 'injury'],
    'suspended wrestler' => [fn (): WrestlerFactory => Wrestler::factory()->suspended(), 'suspension'],
]);

dataset('lifecycle period factories', [
    'activity period' => fn (): ActivityPeriodFactory => ActivityPeriod::factory(),
    'employment' => fn (): EmploymentFactory => Employment::factory(),
    'injury' => fn (): InjuryFactory => Injury::factory(),
    'retirement' => fn (): RetirementFactory => Retirement::factory(),
    'suspension' => fn (): SuspensionFactory => Suspension::factory(),
]);
