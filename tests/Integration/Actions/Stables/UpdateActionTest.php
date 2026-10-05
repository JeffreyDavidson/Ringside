<?php

declare(strict_types=1);

use App\Actions\Stables\UpdateAction;
use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeUpdatedException;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Wrestlers\Wrestler;
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
        members: new StableMembershipData,
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
    $newEnd = $period->started_at->copy()->addHour()->startOfSecond();

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
        members: new StableMembershipData(wrestlers: collect([$wrestler])),
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
