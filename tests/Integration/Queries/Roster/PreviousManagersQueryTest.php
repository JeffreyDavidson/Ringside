<?php

declare(strict_types=1);

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Roster\PreviousManagersQuery;
use Illuminate\Support\Facades\Date;

test('reads the former managers of a stable in last name order in one statement', function (): void {
    // Arrange
    $stable = Stable::factory()
        ->create();
    $wrestler = Wrestler::factory()
        ->create();
    $tagTeam = TagTeam::factory()
        ->create();
    $currentWrestler = Wrestler::factory()
        ->create();
    $formerWrestlerManager = Manager::factory()
        ->create(['first_name' => 'Zoe', 'last_name' => 'Zimmer']);
    $formerTagTeamManager = Manager::factory()
        ->create(['first_name' => 'Abe', 'last_name' => 'Adams']);
    $currentManager = Manager::factory()
        ->create();
    $managedAfterTheyLeft = Manager::factory()
        ->create();
    $elsewhere = Manager::factory()
        ->create();
    $stable->wrestlers()->attach($wrestler, ['joined_at' => Date::now()->subYears(3), 'left_at' => Date::now()->subYear()]);
    $wrestler->managers()->attach($formerWrestlerManager, ['hired_at' => Date::now()->subYears(2), 'fired_at' => Date::now()->subMonths(18)]);
    $wrestler->managers()->attach($managedAfterTheyLeft, ['hired_at' => Date::now()->subMonths(6), 'fired_at' => Date::now()->subMonths(3)]);
    $stable->tagTeams()->attach($tagTeam, ['joined_at' => Date::now()->subYears(2), 'left_at' => Date::now()->subMonths(6)]);
    $tagTeam->managers()->attach($formerTagTeamManager, ['hired_at' => Date::now()->subYear(), 'fired_at' => Date::now()->subMonths(9)]);
    $stable->wrestlers()->attach($currentWrestler, ['joined_at' => Date::now()->subMonths(3)]);
    $currentWrestler->managers()->attach($currentManager, ['hired_at' => Date::now()->subMonths(2)]);
    Wrestler::factory()
        ->create()
        ->managers()
        ->attach($elsewhere, ['hired_at' => Date::now()->subYears(2), 'fired_at' => Date::now()->subYear()]);
    $query = resolve(PreviousManagersQuery::class);

    // Act
    $managers = $query->get($stable->id)->get();
    $statements = recordStatements(fn () => $query->get($stable->id)->get());

    // Assert
    expect($managers->modelKeys())->toBe([$formerTagTeamManager->id, $formerWrestlerManager->id])
        ->and($statements)->toHaveCount(1);
});
