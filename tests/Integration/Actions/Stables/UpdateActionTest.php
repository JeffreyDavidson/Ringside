<?php

declare(strict_types=1);

use App\Actions\Stables\UpdateAction;
use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Models\Roster\Stables\Stable;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
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

test('it establishes a stable that has no activity period when a start date is given', function (?int $daysUntilEnd) {
    $stable = Stable::factory()->withEmployedDefaultMembers()->create(['name' => 'Original Name']);
    $startedAt = now()->subMonth()->startOfSecond();
    $endedAt = $daysUntilEnd === null ? null : $startedAt->copy()->addDays($daysUntilEnd);

    $updatedStable = resolve(UpdateAction::class)->handle($stable, new StableData(
        name: '  Renamed Stable  ',
        start_date: $startedAt,
        members: new StableMembershipData,
        end_date: $endedAt,
    ));

    $activityPeriod = $updatedStable->activityPeriods()->sole();

    expect($updatedStable->name)->toBe('Renamed Stable')
        ->and($activityPeriod->started_at->toDateTimeString())->toBe($startedAt->toDateTimeString())
        ->and($activityPeriod->ended_at?->toDateTimeString())->toBe($endedAt?->toDateTimeString())
        ->and($updatedStable->lifecycleTransitions()
            ->where('transition', LifecycleTransitionType::Established)
            ->exists())->toBeTrue();
})->with([
    'open-ended activity' => [null],
    'activity with an end date' => [10],
]);

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
