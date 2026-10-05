<?php

declare(strict_types=1);

use App\Livewire\Components\GeneralInfo;
use App\Livewire\Managers\Components\Actions as ManagerActions;
use App\Livewire\Referees\Components\Actions as RefereeActions;
use App\Livewire\Stables\Components\Actions as StableActions;
use App\Livewire\TagTeams\Components\Actions as TagTeamActions;
use App\Livewire\Titles\Components\Actions as TitleActions;
use App\Livewire\Wrestlers\Components\Actions as WrestlerActions;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

/**
 * @return list<string> The SQL of every fallback `select exists(...)` status query issued by the callback.
 */
function fallbackStatusQueries(Closure $callback): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    $queries = array_column(DB::getQueryLog(), 'query');
    DB::disableQueryLog();

    return array_values(array_filter($queries, fn (string $sql): bool => str_starts_with($sql, 'select exists')));
}

describe('lifecycle status projection', function (): void {
    test('it renders the general info card without fallback status queries', function (Closure $makeModel): void {
        // Arrange
        $model = $makeModel();
        actingAs(administrator());

        // Act
        $queries = fallbackStatusQueries(fn () => livewire(GeneralInfo::class, ['model' => $model]));

        // Assert
        expect($queries)->toBeEmpty();
    })->with([
        'wrestler' => [fn (): Model => Wrestler::factory()->employed()->create()],
        'manager' => [fn (): Model => Manager::factory()->employed()->create()],
        'referee' => [fn (): Model => Referee::factory()->employed()->create()],
        'tag team' => [fn (): Model => TagTeam::factory()->employed()->withCurrentWrestlers(Wrestler::factory()->employed()->count(2))->create()],
        'stable' => [fn (): Model => Stable::factory()->active()->create()],
        'title' => [fn (): Model => Title::factory()->active()->create()],
    ]);

    test('it re-renders the lifecycle actions without fallback status queries', function (
        Closure $makeModel,
        string $component,
        string $property,
    ): void {
        // Arrange
        $model = $makeModel();
        actingAs(administrator());

        // Act
        $queries = fallbackStatusQueries(fn () => livewire($component, [$property => $model])->call('$refresh'));

        // Assert
        expect($queries)->toBeEmpty();
    })->with([
        'wrestler' => [fn (): Model => Wrestler::factory()->employed()->create(), WrestlerActions::class, 'wrestler'],
        'manager' => [fn (): Model => Manager::factory()->employed()->create(), ManagerActions::class, 'manager'],
        'referee' => [fn (): Model => Referee::factory()->employed()->create(), RefereeActions::class, 'referee'],
        'tag team' => [fn (): Model => TagTeam::factory()->employed()->create(), TagTeamActions::class, 'tagTeam'],
        'stable' => [fn (): Model => Stable::factory()->active()->create(), StableActions::class, 'stable'],
        'title' => [fn (): Model => Title::factory()->active()->create(), TitleActions::class, 'title'],
    ]);
});
