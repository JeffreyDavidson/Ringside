<?php

declare(strict_types=1);

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('show page query counts', function (): void {
    test('it renders the show page without fallback lifecycle status queries', function (
        Closure $makeModel,
        string $routeName,
        int $maxQueries,
        int $expectedAvailabilityQueries,
    ): void {
        // Arrange
        $model = $makeModel();

        actingAs(administrator());
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Act
        $response = get(route($routeName, $model));

        // Assert
        $response
            ->assertOk();

        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();
        expect(array_filter($queries, fn (string $sql): bool => str_starts_with($sql, 'select exists')))->toBeEmpty()
            ->and(count($queries))
            ->toBeLessThanOrEqual($maxQueries)
            ->and(array_filter($queries, fn (string $sql): bool => str_contains($sql, 'roster_unavailabilities')))
            ->toHaveCount($expectedAvailabilityQueries);
    })->with([
        // Availability adds one person lookup and two bounded list counts on individual profiles.
        'wrestler' => [fn (): Model => Wrestler::factory()
            ->employed()
            ->create(), 'wrestlers.show', 16, 2],
        'manager' => [fn (): Model => Manager::factory()
            ->employed()
            ->create(), 'managers.show', 10, 0],
        'referee' => [fn (): Model => Referee::factory()
            ->employed()
            ->create(), 'referees.show', 11, 2],
        'tag team' => [fn (): Model => TagTeam::factory()
            ->employed()
            ->create(), 'tag-teams.show', 13, 0],
        'stable' => [fn (): Model => Stable::factory()
            ->active()
            ->create(), 'stables.show', 12, 0],
        'title' => [fn (): Model => Title::factory()
            ->active()
            ->create(), 'titles.show', 9, 0],
    ]);
});
