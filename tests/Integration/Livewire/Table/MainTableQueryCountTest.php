<?php

declare(strict_types=1);

use App\Livewire\Managers\Tables\Main as ManagersTable;
use App\Livewire\Referees\Tables\Main as RefereesTable;
use App\Livewire\Stables\Tables\Main as StablesTable;
use App\Livewire\TagTeams\Tables\Main as TagTeamsTable;
use App\Livewire\Titles\Tables\Main as TitlesTable;
use App\Livewire\Wrestlers\Tables\Main as WrestlersTable;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

/**
 * @return list<string> The SQL of every query issued by the callback.
 */
function queriesDuring(Closure $callback): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    $queries = array_column(DB::getQueryLog(), 'query');
    DB::disableQueryLog();

    return $queries;
}

dataset('main tables', [
    'wrestlers' => [WrestlersTable::class, fn (int $count) => Wrestler::factory()->employed()->count($count)->create()],
    'managers' => [ManagersTable::class, fn (int $count) => Manager::factory()->employed()->count($count)->create()],
    'referees' => [RefereesTable::class, fn (int $count) => Referee::factory()->employed()->count($count)->create()],
    'tag teams' => [TagTeamsTable::class, fn (int $count) => TagTeam::factory()->employed()->count($count)->create()],
    'stables' => [StablesTable::class, fn (int $count) => Stable::factory()->active()->count($count)->create()],
    'titles' => [TitlesTable::class, fn (int $count) => Title::factory()->active()->count($count)->create()],
]);

describe('main table query counts', function (): void {
    test('it projects row state without per-row fallback queries', function (string $table, Closure $seed): void {
        // Arrange
        $seed(1);
        $queriesWithOneRow = queriesDuring(fn () => livewire($table));
        $seed(9);

        // Act
        $queriesWithTenRows = queriesDuring(fn () => livewire($table));

        // Assert
        expect($queriesWithTenRows)->toHaveSameSize($queriesWithOneRow)
            ->and(array_filter($queriesWithTenRows, fn (string $sql): bool => str_starts_with($sql, 'select exists')))->toBeEmpty();
    })->with('main tables');

    test('it does not recompute status counts when searching', function (string $table, Closure $seed): void {
        // Arrange
        $seed(3);
        $component = livewire($table);

        // Act
        $queries = queriesDuring(fn () => $component->set('search', 'nothing matches this'));

        // Assert
        $countQueries = array_filter($queries, fn (string $sql): bool => str_contains($sql, 'count(*)'));
        expect($countQueries)->toHaveCount(1);
    })->with('main tables');

    test('it recomputes the status counts after a row is deleted', function (): void {
        // Arrange
        $wrestlers = Wrestler::factory()->unemployed()->count(2)->create();
        $component = livewire(WrestlersTable::class);

        // Act
        $component->call('delete', $wrestlers->first());

        // Assert
        expect($component->get('metadataSnapshot')['total'])->toBe(1);
    });

    test('it recomputes the status counts after the table is refreshed', function (): void {
        // Arrange
        Wrestler::factory()->employed()->count(2)->create();
        $component = livewire(WrestlersTable::class);
        Wrestler::factory()->employed()->create();

        // Act
        $component->dispatch('refreshDatatable');

        // Assert
        expect($component->get('metadataSnapshot')['total'])->toBe(3);
    });
});
