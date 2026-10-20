<?php

declare(strict_types=1);

use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeUpdatedException;
use App\Lifecycle\Roster\Stables\StableActivityPeriodRequirements;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;

/**
 * Runs the guard on a stable's periods read oldest first, the way UpdateAction hands them over after locking.
 */
function ensureStablePeriodCanMove(Stable $stable, string $start, ?string $end = null): void
{
    $periods = $stable->activityPeriods()->orderBy('started_at')->orderBy('id')->get();

    resolve(StableActivityPeriodRequirements::class)->ensureCanMove(
        $stable,
        $periods,
        Date::parse($start, 'UTC'),
        $end === null ? null : Date::parse($end, 'UTC'),
    );
}

describe('a stable without activity periods', function (): void {
    it('refuses an end date while establishing', function (): void {
        // Arrange
        $stable = Stable::factory()->unactivated()->create();

        // Act
        $act = fn () => ensureStablePeriodCanMove($stable, '2026-01-01', '2026-02-01');

        // Assert
        expect($act)->toThrow(CannotBeEstablishedException::class);
    });

    it('lets a start date without an end date establish the stable', function (): void {
        // Arrange
        $stable = Stable::factory()->unactivated()->create();

        // Act
        ensureStablePeriodCanMove($stable, '2026-01-01');

        // Assert
        expect($stable->activityPeriods()->exists())->toBeFalse();
    });
});

describe('a stable with several activity periods', function (): void {
    it('refuses a start date on another day than the first period began', function (): void {
        // Arrange
        $stable = Stable::factory()->unactivated()->create();
        $stable->activityPeriods()->create(['started_at' => '2020-01-01', 'ended_at' => '2021-01-01']);
        $stable->activityPeriods()->create(['started_at' => '2022-01-01', 'ended_at' => '2023-01-01']);

        // Act
        $act = fn () => ensureStablePeriodCanMove($stable, '2020-01-02');

        // Assert
        expect($act)->toThrow(CannotBeUpdatedException::class, 'start date cannot be changed');
    });

    it('lets the first period keep its start day, whatever end date is given', function (): void {
        // Arrange
        $stable = Stable::factory()->unactivated()->create();
        $stable->activityPeriods()->create(['started_at' => '2020-01-01 13:30:00', 'ended_at' => '2021-01-01']);
        $stable->activityPeriods()->create(['started_at' => '2022-01-01']);

        // Act
        ensureStablePeriodCanMove($stable, '2020-01-01', '2020-01-01');

        // Assert
        expect($stable->activityPeriods()->count())->toBe(2);
    });
});

describe('a stable with several activity periods in a promotion time zone', function (): void {
    it('lets the first period keep its promotion start day when the stored instant is the previous day in UTC', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $stable = Stable::factory()->unactivated()->for($promotion, 'promotion')->create();
        $stable->activityPeriods()->create(['started_at' => '2020-01-01 02:00:00', 'ended_at' => '2020-06-01 08:00:00']);
        $stable->activityPeriods()->create(['started_at' => '2021-01-01 08:00:00']);

        // Act
        ensureStablePeriodCanMove($stable, '2019-12-31 08:00:00');

        // Assert
        expect($stable->activityPeriods()->count())->toBe(2);
    });

    it('refuses another promotion start day even when the stored instant shares its UTC day', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $stable = Stable::factory()->unactivated()->for($promotion, 'promotion')->create();
        $stable->activityPeriods()->create(['started_at' => '2020-01-01 02:00:00', 'ended_at' => '2020-06-01 08:00:00']);
        $stable->activityPeriods()->create(['started_at' => '2021-01-01 08:00:00']);

        // Act
        $act = fn () => ensureStablePeriodCanMove($stable, '2020-01-01 08:00:00');

        // Assert
        expect($act)->toThrow(CannotBeUpdatedException::class, 'start date cannot be changed');
    });
});

describe('a stable with one activity period', function (): void {
    it('refuses an end date on a period that is still open', function (): void {
        // Arrange
        $stable = Stable::factory()->unactivated()->create();
        $stable->activityPeriods()->create(['started_at' => '2026-01-01']);

        // Act
        $act = fn () => ensureStablePeriodCanMove($stable, '2026-01-01', '2026-02-01');

        // Assert
        expect($act)->toThrow(CannotBeUpdatedException::class, 'cannot be given an end date here');
    });

    it('refuses an end date before the start date when the end is the new end date', function (): void {
        // Arrange
        $stable = Stable::factory()->unactivated()->create();
        $stable->activityPeriods()->create(['started_at' => '2026-01-01', 'ended_at' => '2026-02-01']);

        // Act
        $act = fn () => ensureStablePeriodCanMove($stable, '2026-01-10', '2026-01-09');

        // Assert
        expect($act)->toThrow(InvalidDateRangeException::class);
    });

    it('refuses a start date after the kept end of a closed period', function (): void {
        // Arrange
        $stable = Stable::factory()->unactivated()->create();
        $stable->activityPeriods()->create(['started_at' => '2026-01-01', 'ended_at' => '2026-02-01']);

        // Act
        $act = fn () => ensureStablePeriodCanMove($stable, '2026-02-02');

        // Assert
        expect($act)->toThrow(InvalidDateRangeException::class);
    });

    it('refuses a start date after a member joined', function (Closure $attach): void {
        // Arrange
        $stable = Stable::factory()->unactivated()->create();
        $stable->activityPeriods()->create(['started_at' => '2026-01-01']);
        $attach($stable);

        // Act
        $act = fn () => ensureStablePeriodCanMove($stable, '2026-01-10');

        // Assert
        expect($act)->toThrow(CannotBeUpdatedException::class, 'cannot start after a member joined');
    })->with([
        'wrestler' => [fn (Stable $stable) => $stable->wrestlers()->attach(Wrestler::factory()->create(), ['joined_at' => '2026-01-05'])],
        'tag team' => [fn (Stable $stable) => $stable->tagTeams()->attach(TagTeam::factory()->create(), ['joined_at' => '2026-01-05'])],
    ]);

    it('refuses an end date before a member left', function (Closure $attach): void {
        // Arrange
        $stable = Stable::factory()->unactivated()->create();
        $stable->activityPeriods()->create(['started_at' => '2026-01-01', 'ended_at' => '2026-03-01']);
        $attach($stable);

        // Act
        $act = fn () => ensureStablePeriodCanMove($stable, '2026-01-01', '2026-01-20');

        // Assert
        expect($act)->toThrow(CannotBeUpdatedException::class, 'cannot end before a member left');
    })->with([
        'wrestler' => [fn (Stable $stable) => $stable->wrestlers()->attach(Wrestler::factory()->create(), ['joined_at' => '2026-01-02', 'left_at' => '2026-02-01'])],
        'tag team' => [fn (Stable $stable) => $stable->tagTeams()->attach(TagTeam::factory()->create(), ['joined_at' => '2026-01-02', 'left_at' => '2026-02-01'])],
    ]);

    it('reports the start problem before the end problem when both apply', function (): void {
        // Arrange
        $stable = Stable::factory()->unactivated()->create();
        $stable->activityPeriods()->create(['started_at' => '2026-01-01', 'ended_at' => '2026-03-01']);
        $stable->wrestlers()->attach(Wrestler::factory()->create(), ['joined_at' => '2026-01-02', 'left_at' => '2026-02-01']);

        // Act
        $act = fn () => ensureStablePeriodCanMove($stable, '2026-01-10', '2026-01-20');

        // Assert
        expect($act)->toThrow(CannotBeUpdatedException::class, 'cannot start after a member joined');
    });

    it('does not check memberships for an edge that stays on its promotion day', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $stable = Stable::factory()->unactivated()->for($promotion, 'promotion')->create();
        $stable->activityPeriods()->create(['started_at' => '2026-01-10 20:00:00', 'ended_at' => '2026-03-01 08:00:00']);
        $stable->wrestlers()->attach(Wrestler::factory()->create(), ['joined_at' => '2026-01-05', 'left_at' => '2026-03-02']);

        // Act
        ensureStablePeriodCanMove($stable, '2026-01-10 21:00:00', '2026-03-01 09:00:00');

        // Assert
        expect($stable->wrestlers()->count())->toBe(1);
    });

    it('keeps the end of an open period open and the end of a closed period unless a new one is given', function (?string $periodEnd, ?string $newEnd, ?string $expected): void {
        // Arrange
        $stable = Stable::factory()->unactivated()->create();
        $period = $stable->activityPeriods()->create(['started_at' => '2026-01-01', 'ended_at' => $periodEnd]);
        $newEndDate = $newEnd === null ? null : Date::parse($newEnd, 'UTC');

        // Act
        $end = resolve(StableActivityPeriodRequirements::class)->endAfterMove($period->refresh(), $newEndDate);

        // Assert
        expect($end?->toDateString())->toBe($expected);
    })->with([
        'open period' => [null, null, null],
        'closed period without a new end' => ['2026-02-01', null, '2026-02-01'],
        'closed period with a new end' => ['2026-02-01', '2026-03-01', '2026-03-01'],
    ]);
});
