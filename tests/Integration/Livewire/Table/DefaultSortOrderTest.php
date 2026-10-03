<?php

declare(strict_types=1);

use App\Livewire\Events\Tables\Main as EventsTable;
use App\Livewire\Managers\Tables\Main as ManagersTable;
use App\Livewire\Referees\Tables\Main as RefereesTable;
use App\Livewire\Stables\Tables\Main as StablesTable;
use App\Livewire\TagTeams\Tables\Main as TagTeamsTable;
use App\Livewire\Titles\Tables\Main as TitlesTable;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportTesting\Testable;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

/*
 * Every record is inserted out of its expected position: the rows that differ in the sort column are inserted in
 * reverse, and the two rows that tie on it get explicit ids in descending insertion order, so only the table's own
 * ORDER BY (and its id tie-break) can produce the expected sequence. The tie-break itself is proven by the opt-in
 * REVERSE_UNORDERED_SELECTS=1 run and by PostgreSQL, which returns ties in insertion order.
 */

beforeEach(function (): void {
    actingAs(administrator());
});

/**
 * The ids of the rows the table renders on its first page, in display order.
 *
 * @return array<int, mixed>
 */
function renderedRowIds(Testable $table): array
{
    $rows = $table->viewData('rows');

    if (! $rows instanceof LengthAwarePaginator) {
        throw new RuntimeException('Expected the table to render a page of rows.');
    }

    return collect($rows->items())
        ->map(fn (mixed $row): mixed => $row instanceof Model ? $row->getKey() : null)
        ->all();
}

/**
 * Create records that sort by name: two distinct names inserted in reverse and two namesakes from different
 * promotions (names are only unique within a promotion) inserted with descending ids.
 *
 * @param  Closure(string, array<string, mixed>): Model  $create
 * @return list<int|string> The ids in the expected display order
 */
function recordsSortedByName(Closure $create): array
{
    $last = $create('Zulu', []);
    $first = $create('Alpha', []);
    $secondNamesake = $create('Middle', ['id' => 9002, 'promotion_id' => Promotion::factory()->create()->id]);
    $firstNamesake = $create('Middle', ['id' => 9001, 'promotion_id' => Promotion::factory()->create()->id]);

    return [$first->getKey(), $firstNamesake->getKey(), $secondNamesake->getKey(), $last->getKey()];
}

/**
 * Create people that sort by last name, inserted the same way as recordsSortedByName().
 *
 * @param  Closure(array<string, mixed>): Model  $create
 * @return list<int|string> The ids in the expected display order
 */
function peopleSortedByLastName(Closure $create): array
{
    $last = $create(['first_name' => 'Amy', 'last_name' => 'Young']);
    $first = $create(['first_name' => 'Zoe', 'last_name' => 'Brown']);
    $secondNamesake = $create(['id' => 9002, 'first_name' => 'Adam', 'last_name' => 'Smith']);
    $firstNamesake = $create(['id' => 9001, 'first_name' => 'Zack', 'last_name' => 'Smith']);

    return [$first->getKey(), $firstNamesake->getKey(), $secondNamesake->getKey(), $last->getKey()];
}

test('the :dataset index lists its rows in a fixed default order', function (string $component, Closure $createRecordsInExpectedOrder): void {
    // Arrange
    $expectedIds = $createRecordsInExpectedOrder();

    // Act
    $table = livewire($component);

    // Assert
    expect(renderedRowIds($table))->toBe($expectedIds);
})->with([
    'managers' => [
        ManagersTable::class,
        fn (): array => peopleSortedByLastName(fn (array $attributes): Manager => Manager::factory()->create($attributes)),
    ],
    'referees' => [
        RefereesTable::class,
        fn (): array => peopleSortedByLastName(fn (array $attributes): Referee => Referee::factory()->create($attributes)),
    ],
    'stables' => [
        StablesTable::class,
        fn (): array => recordsSortedByName(fn (string $name, array $attributes): Stable => Stable::factory()->create([...$attributes, 'name' => "{$name} Faction"])),
    ],
    'tag teams' => [
        TagTeamsTable::class,
        fn (): array => recordsSortedByName(fn (string $name, array $attributes): TagTeam => TagTeam::factory()->create([...$attributes, 'name' => "{$name} Express"])),
    ],
    'titles' => [
        TitlesTable::class,
        fn (): array => recordsSortedByName(fn (string $name, array $attributes): Title => Title::factory()->create([...$attributes, 'name' => "{$name} Title"])),
    ],
]);

test('the events index lists the latest dated events first, then undated ones, in a fixed order', function (): void {
    // Arrange
    $undated = Event::factory()->unscheduled()->create();
    $oldest = Event::factory()->create(['date' => Carbon::parse('2024-01-01 19:00')]);
    $latest = Event::factory()->create(['date' => Carbon::parse('2024-03-01 19:00')]);
    $secondSameDay = Event::factory()->create(['id' => 9002, 'date' => Carbon::parse('2024-02-01 19:00')]);
    $firstSameDay = Event::factory()->create(['id' => 9001, 'date' => Carbon::parse('2024-02-01 19:00')]);

    // Act
    $table = livewire(EventsTable::class);

    // Assert
    expect(renderedRowIds($table))->toBe([
        $latest->id,
        $firstSameDay->id,
        $secondSameDay->id,
        $oldest->id,
        $undated->id,
    ]);
});
