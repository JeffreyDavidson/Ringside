<?php

declare(strict_types=1);

use App\Actions\Stables\UpdateAction;
use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeUpdatedException;
use App\Lifecycle\Roster\Stables\StableNameLock;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\Stables\StableMembershipService;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

test('it rejects an activity end date before the start date', function () {
    $stable = Stable::factory()->inactive()->create(['name' => 'Original Name']);
    $originalPeriod = $stable->firstActivityPeriod()->firstOrFail();
    $startedAt = now()->subMonth();

    $data = new StableData(
        name: 'Updated Name',
        start_date: $startedAt,
        members: new StableMembershipData,
        end_date: $startedAt->copy()->subSecond(),
    );

    expect(fn () => resolve(UpdateAction::class)->handle($stable, $data))
        ->toThrow(InvalidDateRangeException::class)
        ->and($stable->refresh()->name)->toBe('Original Name')
        ->and($originalPeriod->refresh()->started_at->toDateTimeString())->toBe($originalPeriod->started_at->toDateTimeString());
});

test('it establishes a stable that has no activity period when a start date is given', function () {
    $stable = Stable::factory()->withEmployedDefaultMembers()->create(['name' => 'Original Name']);
    $startedAt = now()->subMonth()->startOfSecond();

    $updatedStable = resolve(UpdateAction::class)->handle($stable, new StableData(
        name: '  Renamed Stable  ',
        start_date: $startedAt,
        members: resolve(StableMembershipService::class)->currentMembers($stable),
    ));

    $activityPeriod = $updatedStable->activityPeriods()->sole();

    expect($updatedStable->name)->toBe('Renamed Stable')
        ->and($activityPeriod->started_at->toDateTimeString())->toBe($startedAt->toDateTimeString())
        ->and($activityPeriod->ended_at)->toBeNull()
        ->and($updatedStable->lifecycleTransitions()
            ->where('transition', LifecycleTransitionType::Established)
            ->exists())->toBeTrue();
});

test('it rejects an end date while establishing a stable that has no activity period', function () {
    // Arrange
    $stable = Stable::factory()->withEmployedDefaultMembers()->create(['name' => 'Original Name']);
    $startedAt = now()->subMonth()->startOfSecond();
    $data = new StableData(
        name: 'Renamed Stable',
        start_date: $startedAt,
        members: new StableMembershipData,
        end_date: $startedAt->copy()->addDays(10),
    );

    // Act
    $update = fn () => resolve(UpdateAction::class)->handle($stable, $data);

    // Assert
    expect($update)->toThrow(CannotBeEstablishedException::class)
        ->and($stable->refresh()->name)->toBe('Original Name')
        ->and($stable->activityPeriods()->exists())->toBeFalse()
        ->and($stable->currentWrestlers()->exists())->toBeTrue();
});

test('it does not create an activity period when no start date is given', function () {
    $stable = Stable::factory()->unactivated()->create(['name' => 'Original Name']);

    $updatedStable = resolve(UpdateAction::class)->handle($stable, new StableData(
        name: 'Renamed Stable',
        start_date: null,
        members: new StableMembershipData,
    ));

    expect($updatedStable->name)->toBe('Renamed Stable')
        ->and($updatedStable->activityPeriods()->exists())->toBeFalse();
});

test('it never combines a row lock with a grouped query when updating an existing activity period', function () {
    $stable = Stable::factory()->inactive()->create();
    $startedAt = now()->subMonth()->startOfSecond();
    $connection = DB::connection();
    $originalGrammar = $connection->getQueryGrammar();

    // SQLite discards row-lock clauses, which hides "FOR UPDATE is not allowed with GROUP BY"
    // errors that PostgreSQL raises. Render the lock as a comment so the executed SQL exposes it.
    // PostgreSQL already emits the real clause (and rejects the query itself).
    if ($connection->getDriverName() === 'sqlite') {
        $connection->setQueryGrammar(new class($connection) extends SQLiteGrammar
        {
            protected function compileLock(Builder $query, $value): string
            {
                return $value ? ' /* for update */' : ' /* for share */';
            }
        });
    }

    $executedSql = [];
    DB::listen(function ($query) use (&$executedSql): void {
        $executedSql[] = mb_strtolower($query->sql);
    });

    try {
        resolve(UpdateAction::class)->handle($stable, new StableData(
            name: 'Renamed Stable',
            start_date: $startedAt,
            members: new StableMembershipData,
        ));
    } finally {
        $connection->setQueryGrammar($originalGrammar);
    }

    $lockedSql = collect($executedSql)->filter(fn (string $sql): bool => str_contains($sql, 'for update'));

    expect($lockedSql)->not->toBeEmpty()
        ->and($lockedSql->filter(fn (string $sql): bool => str_contains($sql, 'group by')))->toBeEmpty();
});

test('it never reopens a disbanded stable when the end date is blank', function () {
    $stable = Stable::factory()->inactive()->create();
    $period = $stable->firstActivityPeriod()->firstOrFail();

    resolve(UpdateAction::class)->handle($stable, new StableData(
        name: $stable->name,
        start_date: $period->started_at,
        members: new StableMembershipData,
        end_date: null,
    ));

    expect($stable->activityPeriods()->whereNull('ended_at')->exists())->toBeFalse()
        ->and($period->refresh()->ended_at)->not->toBeNull();
});

test('it does not touch the end of an earlier period when a later period is open', function () {
    $stable = Stable::factory()->active()->create();
    $first = $stable->firstActivityPeriod()->firstOrFail();
    $firstEnd = $first->started_at->copy()->addDay()->startOfSecond();
    $first->update(['ended_at' => $firstEnd]);
    $stable->activityPeriods()->create(['started_at' => now()->subHours(2)]);

    resolve(UpdateAction::class)->handle($stable, new StableData(
        name: $stable->name,
        start_date: $first->started_at,
        members: new StableMembershipData,
        end_date: null,
    ));

    expect($first->refresh()->ended_at?->toDateTimeString())->toBe($firstEnd->toDateTimeString())
        ->and($stable->activityPeriods()->whereNull('ended_at')->count())->toBe(1);
});

test('it can still move the end date of a disbanded stable with a single period', function () {
    $stable = Stable::factory()->inactive()->create();
    $period = $stable->firstActivityPeriod()->firstOrFail();
    $newEnd = $period->started_at->copy()->addDays(2)->startOfSecond();

    resolve(UpdateAction::class)->handle($stable, new StableData(
        name: $stable->name,
        start_date: $period->started_at,
        members: new StableMembershipData,
        end_date: $newEnd,
    ));

    expect($period->refresh()->ended_at?->toDateTimeString())->toBe($newEnd->toDateTimeString());
});

test('it rejects a new start date after the existing end of the only activity period', function () {
    $stable = Stable::factory()->inactive()->create(['name' => 'Original Name']);
    $originalPeriod = $stable->firstActivityPeriod()->firstOrFail();
    $originalStart = $originalPeriod->started_at->toDateTimeString();
    $originalEnd = $originalPeriod->ended_at?->toDateTimeString();

    $data = new StableData(
        name: 'Updated Name',
        start_date: now()->subHours(12),
        members: new StableMembershipData,
    );

    expect(fn () => resolve(UpdateAction::class)->handle($stable, $data))
        ->toThrow(InvalidDateRangeException::class)
        ->and($stable->refresh()->name)->toBe('Original Name')
        ->and($originalPeriod->refresh()->started_at->toDateTimeString())->toBe($originalStart)
        ->and($originalPeriod->ended_at?->toDateTimeString())->toBe($originalEnd);
});

test('it rejects moving the start date of a stable with several activity periods', function (string $newStart) {
    // Arrange
    $stable = Stable::factory()->unactivated()->create(['name' => 'Original Name']);
    $first = $stable->activityPeriods()->create(['started_at' => '2020-01-01', 'ended_at' => '2021-01-01']);
    $second = $stable->activityPeriods()->create(['started_at' => '2022-01-01', 'ended_at' => '2023-01-01']);
    $data = new StableData(
        name: 'Updated Name',
        start_date: Date::parse($newStart),
        members: new StableMembershipData,
    );

    // Act
    $update = fn () => resolve(UpdateAction::class)->handle($stable, $data);

    // Assert
    expect($update)->toThrow(CannotBeUpdatedException::class)
        ->and($stable->refresh()->name)->toBe('Original Name')
        ->and($first->refresh()->started_at->toDateString())->toBe('2020-01-01')
        ->and($first->ended_at?->toDateString())->toBe('2021-01-01')
        ->and($second->refresh()->started_at->toDateString())->toBe('2022-01-01')
        ->and($second->ended_at?->toDateString())->toBe('2023-01-01');
})->with([
    'after the first period ends' => ['2021-06-01'],
    'within the first period' => ['2020-06-01'],
]);

test('it leaves every period untouched when a stable with several periods keeps its start date', function () {
    // Arrange
    $stable = Stable::factory()->unactivated()->create();
    $first = $stable->activityPeriods()->create(['started_at' => '2020-01-01 13:30:00', 'ended_at' => '2021-01-01']);
    $second = $stable->activityPeriods()->create(['started_at' => '2022-01-01', 'ended_at' => '2023-01-01']);

    // Act
    resolve(UpdateAction::class)->handle($stable, new StableData(
        name: 'Renamed Stable',
        start_date: Date::parse('2020-01-01'),
        members: new StableMembershipData,
        end_date: Date::parse('2021-01-01'),
    ));

    // Assert
    expect($stable->refresh()->name)->toBe('Renamed Stable')
        ->and($first->refresh()->started_at->toDateTimeString())->toBe('2020-01-01 13:30:00')
        ->and($second->refresh()->started_at->toDateString())->toBe('2022-01-01');
});

test('it rejects an end date on an open activity period instead of closing it', function () {
    // Arrange
    $stable = Stable::factory()->active()->create(['name' => 'Original Name']);
    $period = $stable->firstActivityPeriod()->firstOrFail();
    $data = new StableData(
        name: 'Renamed Stable',
        start_date: $period->started_at,
        members: new StableMembershipData,
        end_date: now()->subHour(),
    );

    // Act
    $update = fn () => resolve(UpdateAction::class)->handle($stable, $data);

    // Assert
    expect($update)->toThrow(CannotBeUpdatedException::class)
        ->and($stable->refresh()->name)->toBe('Original Name')
        ->and($period->refresh()->ended_at)->toBeNull()
        ->and($stable->currentWrestlers()->exists())->toBeTrue()
        ->and($stable->currentTagTeams()->exists())->toBeTrue();
});

test('it rejects giving a disbanded stable members', function () {
    // Arrange
    $stable = Stable::factory()->inactive()->create();
    $wrestler = Wrestler::factory()->bookable()->create();
    $period = $stable->firstActivityPeriod()->firstOrFail();

    // Act
    $addMembers = fn () => resolve(UpdateAction::class)->handle($stable, new StableData(
        name: $stable->name,
        start_date: $period->started_at,
        members: new StableMembershipData(wrestlers: Wrestler::query()->whereKey($wrestler->getKey())->get()),
    ));

    // Assert
    expect($addMembers)->toThrow(CannotBeUpdatedException::class)
        ->and($stable->currentWrestlers()->exists())->toBeFalse();
});

test('it removes members left on a disbanded stable when the edit selects none', function () {
    // Arrange
    $stable = Stable::factory()->inactive()->create();
    $period = $stable->firstActivityPeriod()->firstOrFail();
    $wrestler = Wrestler::factory()->bookable()->create();
    $stable->wrestlers()->attach($wrestler, ['joined_at' => $period->started_at]);

    // Act
    resolve(UpdateAction::class)->handle($stable, new StableData(
        name: $stable->name,
        start_date: $period->started_at,
        members: new StableMembershipData(wrestlers: collect()),
    ));

    // Assert
    expect($stable->currentWrestlers()->exists())->toBeFalse()
        ->and($stable->previousWrestlers()->whereKey($wrestler->getKey())->exists())->toBeTrue();
});

test('it locks the new name of a stable without a promotion before the stable row', function () {
    // Arrange
    $stable = Stable::factory()->create(['name' => 'Original Name']);
    $data = new StableData(name: '  Updated Name  ', start_date: null, members: new StableMembershipData);
    $nameKey = resolve(StableNameLock::class)->key('Updated Name');

    // Act
    $statements = recordStatements(fn () => resolve(UpdateAction::class)->handle($stable, $data));

    // Assert
    $lockPosition = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "stable_name_locks"'));
    $stableLockPosition = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "stables"'));

    expect($statements[$lockPosition]['bindings'])->toBe([$nameKey])
        ->and($lockPosition)->toBeLessThan($stableLockPosition);
});

test('it takes no name lock for a stable of a promotion', function () {
    // Arrange
    $stable = Stable::factory()->for(Promotion::factory(), 'promotion')->create(['name' => 'Original Name']);
    $data = new StableData(name: 'Updated Name', start_date: null, members: new StableMembershipData);

    // Act
    resolve(UpdateAction::class)->handle($stable, $data);

    // Assert
    expect(DB::table('stable_name_locks')->exists())->toBeFalse();
});

test('it rejects a name another active stable already uses', function (bool $inPromotion) {
    // Arrange
    $promotion = $inPromotion ? Promotion::factory()->create() : null;
    Stable::factory()->state(['promotion_id' => $promotion?->id, 'name' => 'Taken Name'])->create();
    $stable = Stable::factory()->state(['promotion_id' => $promotion?->id, 'name' => 'Original Name'])->create();
    $data = new StableData(name: ' Taken Name ', start_date: null, members: new StableMembershipData);

    // Act
    $update = fn () => resolve(UpdateAction::class)->handle($stable, $data);

    // Assert
    expect($update)->toThrow(CannotBeUpdatedException::class, "an active stable named 'Taken Name' already exists")
        ->and($stable->refresh()->name)->toBe('Original Name');
})->with([
    'without a promotion' => [false],
    'in a promotion' => [true],
]);

test('it keeps the name of the stable being updated', function () {
    // Arrange
    $stable = Stable::factory()->create(['name' => 'Same Name']);
    $data = new StableData(name: 'Same Name', start_date: null, members: new StableMembershipData);

    // Act
    $updated = resolve(UpdateAction::class)->handle($stable, $data);

    // Assert
    expect($updated->name)->toBe('Same Name');
});

test('it reports a name taken when a concurrent rename wins the promotion name between the check and the write', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $stable = Stable::factory()->for($promotion, 'promotion')->create(['name' => 'Old Name']);
    $data = new StableData(name: 'The Alliance', start_date: null, members: new StableMembershipData);
    Stable::updating(function () use ($promotion): void {
        Stable::withoutEvents(fn () => Stable::factory()->for($promotion, 'promotion')->create(['name' => 'The Alliance']));
    });

    // Act
    $update = fn () => resolve(UpdateAction::class)->handle($stable, $data);

    // Assert
    expect($update)->toThrow(CannotBeUpdatedException::class, "an active stable named 'The Alliance' already exists")
        ->and($stable->refresh()->name)->toBe('Old Name');
});

describe('moving the period of a stable with memberships', function () {
    test('it refuses a start date after a membership began and writes nothing', function () {
        // Arrange
        $stable = Stable::factory()->create(['name' => 'Original Name']);
        $stable->activityPeriods()->create(['started_at' => '2026-01-01']);
        $wrestler = Wrestler::factory()->bookable()->create();
        $stable->wrestlers()->attach($wrestler, ['joined_at' => '2026-01-05']);
        $data = new StableData(
            name: 'Updated Name',
            start_date: Date::parse('2026-01-10'),
            members: new StableMembershipData(wrestlers: Wrestler::query()->whereKey($wrestler->getKey())->get()),
        );

        // Act
        $update = fn () => resolve(UpdateAction::class)->handle($stable, $data);

        // Assert
        expect($update)->toThrow(CannotBeUpdatedException::class, 'cannot start after a member joined')
            ->and($stable->refresh()->name)->toBe('Original Name')
            ->and($stable->activityPeriods()->sole()->started_at->toDateString())->toBe('2026-01-01')
            ->and(DB::table($stable->wrestlers()->getTable())->value('joined_at'))->toStartWith('2026-01-05');
    });

    test('it refuses an end date before a membership ended and writes nothing', function () {
        // Arrange
        $stable = Stable::factory()->create(['name' => 'Original Name']);
        $stable->activityPeriods()->create(['started_at' => '2026-01-01', 'ended_at' => '2026-02-01']);
        $tagTeam = TagTeam::factory()->create();
        $stable->tagTeams()->attach($tagTeam, ['joined_at' => '2026-01-02', 'left_at' => '2026-02-01']);
        $data = new StableData(
            name: 'Updated Name',
            start_date: Date::parse('2026-01-01'),
            members: new StableMembershipData,
            end_date: Date::parse('2026-01-20'),
        );

        // Act
        $update = fn () => resolve(UpdateAction::class)->handle($stable, $data);

        // Assert
        expect($update)->toThrow(CannotBeUpdatedException::class, 'cannot end before a member left')
            ->and($stable->refresh()->name)->toBe('Original Name')
            ->and($stable->activityPeriods()->sole()->ended_at?->toDateString())->toBe('2026-02-01')
            ->and(DB::table($stable->tagTeams()->getTable())->value('left_at'))->toStartWith('2026-02-01');
    });

    test('it accepts dates that still cover every membership, including the same day', function () {
        // Arrange
        $stable = Stable::factory()->create();
        $stable->activityPeriods()->create(['started_at' => '2026-01-01', 'ended_at' => '2026-02-01']);
        $wrestler = Wrestler::factory()->create();
        $stable->wrestlers()->attach($wrestler, ['joined_at' => '2026-01-05 15:00:00', 'left_at' => '2026-02-01 09:00:00']);
        $data = new StableData(
            name: 'Updated Name',
            start_date: Date::parse('2026-01-05'),
            members: new StableMembershipData,
            end_date: Date::parse('2026-02-01'),
        );

        // Act
        resolve(UpdateAction::class)->handle($stable, $data);

        // Assert
        $period = $stable->activityPeriods()->sole();
        expect($period->started_at->toDateString())->toBe('2026-01-05')
            ->and($period->ended_at?->toDateString())->toBe('2026-02-01');
    });

    test('it accepts a period that starts and ends on the same day', function () {
        // Arrange
        $stable = Stable::factory()->create();
        $stable->activityPeriods()->create(['started_at' => '2026-01-01', 'ended_at' => '2026-02-01']);
        $data = new StableData(
            name: 'Updated Name',
            start_date: Date::parse('2026-01-10'),
            members: new StableMembershipData,
            end_date: Date::parse('2026-01-10'),
        );

        // Act
        resolve(UpdateAction::class)->handle($stable, $data);

        // Assert
        $period = $stable->activityPeriods()->sole();
        expect($period->started_at->toDateString())->toBe('2026-01-10')
            ->and($period->ended_at?->toDateString())->toBe('2026-01-10');
    });

    test('it leaves memberships alone when the dates are unchanged', function () {
        // Arrange
        $stable = Stable::factory()->create();
        $stable->activityPeriods()->create(['started_at' => '2026-01-10']);
        $wrestler = Wrestler::factory()->create();
        $stable->wrestlers()->attach($wrestler, ['joined_at' => '2026-01-05']);
        $data = new StableData(
            name: 'Renamed',
            start_date: Date::parse('2026-01-10'),
            members: new StableMembershipData(wrestlers: Wrestler::query()->whereKey($wrestler->getKey())->get()),
        );

        // Act
        $updated = resolve(UpdateAction::class)->handle($stable, $data);

        // Assert
        expect($updated->name)->toBe('Renamed');
    });
});

describe('membership dates are compared as promotion days', function () {
    test('it refuses a start day that is after the day a member joined in the promotion time zone', function () {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $stable = Stable::factory()->for($promotion, 'promotion')->create(['name' => 'Original Name']);
        $stable->activityPeriods()->create(['started_at' => '2026-01-01 08:00:00']);
        $wrestler = Wrestler::factory()->create();
        // 2026-01-10 19:00 in Los Angeles, which is already the 11th in UTC.
        $stable->wrestlers()->attach($wrestler, ['joined_at' => '2026-01-11 03:00:00']);
        $data = new StableData(
            name: 'Updated Name',
            // The start of 2026-01-11 in Los Angeles.
            start_date: Date::parse('2026-01-11 08:00:00', 'UTC'),
            members: new StableMembershipData(wrestlers: Wrestler::query()->whereKey($wrestler->getKey())->get()),
        );

        // Act
        $update = fn () => resolve(UpdateAction::class)->handle($stable, $data);

        // Assert
        expect($update)->toThrow(CannotBeUpdatedException::class, 'cannot start after a member joined');
    });

    test('it accepts an end day that is the day a member left in the promotion time zone', function () {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $stable = Stable::factory()->for($promotion, 'promotion')->create();
        $stable->activityPeriods()->create(['started_at' => '2026-01-01 08:00:00', 'ended_at' => '2026-02-01 08:00:00']);
        $tagTeam = TagTeam::factory()->create();
        // 2026-01-10 21:00 in Los Angeles, which is already the 11th in UTC.
        $stable->tagTeams()->attach($tagTeam, ['joined_at' => '2026-01-02 08:00:00', 'left_at' => '2026-01-11 05:00:00']);
        $data = new StableData(
            name: 'Updated Name',
            start_date: Date::parse('2026-01-01 08:00:00', 'UTC'),
            members: new StableMembershipData,
            end_date: Date::parse('2026-01-10 08:00:00', 'UTC'),
        );

        // Act
        resolve(UpdateAction::class)->handle($stable, $data);

        // Assert
        expect($stable->activityPeriods()->sole()->ended_at?->toDateTimeString())->toBe('2026-01-10 08:00:00');
    });
});

/*
 * The several-periods check compares the stored start and the submitted start as promotion days, the way the form
 * (CanChangeDebutDate) and the single-period check do: the same promotion day is unchanged, another one is locked.
 */
describe('start date of a stable with several periods around promotion midnight', function () {
    test('it accepts an unchanged promotion start day when the stored instant is the previous day in UTC', function () {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $stable = Stable::factory()->unactivated()->for($promotion, 'promotion')->create();
        // 2019-12-31 18:00 in Los Angeles, already 2020-01-01 in UTC; the form shows this period as starting 2019-12-31.
        $stable->activityPeriods()->create(['started_at' => '2020-01-01 02:00:00', 'ended_at' => '2020-06-01 08:00:00']);
        $stable->activityPeriods()->create(['started_at' => '2021-01-01 08:00:00', 'ended_at' => '2021-06-01 08:00:00']);
        $data = new StableData(
            name: 'Renamed Stable',
            // The start of 2019-12-31 in Los Angeles: the day the form shows, resubmitted unchanged.
            start_date: Date::parse('2019-12-31 08:00:00', 'UTC'),
            members: new StableMembershipData,
            end_date: Date::parse('2020-06-01 08:00:00', 'UTC'),
        );

        // Act
        resolve(UpdateAction::class)->handle($stable, $data);

        // Assert
        expect($stable->activityPeriods()->count())->toBe(2)
            ->and($stable->activityPeriods()->orderBy('started_at')->pluck('started_at')->map->toDateTimeString()->all())->toBe(['2020-01-01 02:00:00', '2021-01-01 08:00:00']);
    });

    test('it refuses a different promotion start day when the stored instant shares its UTC day, and writes nothing', function () {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $stable = Stable::factory()->unactivated()->for($promotion, 'promotion')->create(['name' => 'Original Name']);
        // 2019-12-31 18:00 in Los Angeles, already 2020-01-01 in UTC.
        $first = $stable->activityPeriods()->create(['started_at' => '2020-01-01 02:00:00', 'ended_at' => '2020-06-01 08:00:00']);
        $stable->activityPeriods()->create(['started_at' => '2021-01-01 08:00:00', 'ended_at' => '2021-06-01 08:00:00']);
        $data = new StableData(
            name: 'Renamed Stable',
            // The start of 2020-01-01 in Los Angeles: a different promotion day than the stored one.
            start_date: Date::parse('2020-01-01 08:00:00', 'UTC'),
            members: new StableMembershipData,
        );

        // Act
        $update = fn () => resolve(UpdateAction::class)->handle($stable, $data);

        // Assert
        expect($update)->toThrow(CannotBeUpdatedException::class, 'start date cannot be changed')
            ->and($stable->refresh()->name)->toBe('Original Name')
            ->and($first->refresh()->started_at->toDateTimeString())->toBe('2020-01-01 02:00:00');
    });

    test('it treats the same promotion day as unchanged for a stable with a single period', function () {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $stable = Stable::factory()->unactivated()->for($promotion, 'promotion')->create();
        $stable->activityPeriods()->create(['started_at' => '2020-01-01 02:00:00', 'ended_at' => '2020-06-01 08:00:00']);
        // A member who joined before the stored instant: moving the start to the 31st would be refused if it counted as a move.
        $stable->wrestlers()->attach(Wrestler::factory()->create(), ['joined_at' => '2020-01-01 01:00:00']);
        $data = new StableData(
            name: 'Renamed Stable',
            // The same promotion day as the stored instant (2019-12-31), resubmitted unchanged.
            start_date: Date::parse('2019-12-31 08:00:00', 'UTC'),
            members: new StableMembershipData,
            end_date: Date::parse('2020-06-01 08:00:00', 'UTC'),
        );

        // Act
        resolve(UpdateAction::class)->handle($stable, $data);

        // Assert
        expect($stable->activityPeriods()->sole()->started_at->toDateTimeString())->toBe('2019-12-31 08:00:00');
    });
});
