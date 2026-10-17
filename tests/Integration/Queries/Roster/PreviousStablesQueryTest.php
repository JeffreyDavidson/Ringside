<?php

declare(strict_types=1);

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Roster\PreviousStablesQuery;
use Illuminate\Support\Facades\Date;

test('reads the stables a manager used to manage a member of, in name order, in one statement', function (): void {
    // Arrange
    $manager = Manager::factory()
        ->create();
    $wrestler = Wrestler::factory()
        ->create();
    $tagTeam = TagTeam::factory()
        ->create();
    $currentWrestler = Wrestler::factory()
        ->create();
    $formerViaWrestler = Stable::factory()
        ->create(['name' => 'Zulu Stable']);
    $formerViaTagTeam = Stable::factory()
        ->create(['name' => 'Alpha Stable']);
    $current = Stable::factory()
        ->create();
    $managedAfterTheyLeft = Stable::factory()
        ->create();
    $formerViaWrestler->wrestlers()->attach($wrestler, ['joined_at' => Date::now()->subYears(3), 'left_at' => Date::now()->subYear()]);
    $wrestler->managers()->attach($manager, ['hired_at' => Date::now()->subYears(2), 'fired_at' => Date::now()->subMonths(18)]);
    $formerViaTagTeam->tagTeams()->attach($tagTeam, ['joined_at' => Date::now()->subYears(2), 'left_at' => Date::now()->subMonths(6)]);
    $tagTeam->managers()->attach($manager, ['hired_at' => Date::now()->subYear(), 'fired_at' => Date::now()->subMonths(9)]);
    $current->wrestlers()->attach($currentWrestler, ['joined_at' => Date::now()->subMonths(3)]);
    $currentWrestler->managers()->attach($manager, ['hired_at' => Date::now()->subMonths(2)]);
    $managedAfterTheyLeft->wrestlers()->attach($wrestler, ['joined_at' => Date::now()->subYears(5), 'left_at' => Date::now()->subYears(4)]);
    $query = resolve(PreviousStablesQuery::class);

    // Act
    $stables = $query->get($manager->id)->get();
    $statements = recordStatements(fn () => $query->get($manager->id)->get());

    // Assert
    expect($stables->modelKeys())->toBe([$formerViaTagTeam->id, $formerViaWrestler->id])
        ->and($statements)->toHaveCount(1);
});
