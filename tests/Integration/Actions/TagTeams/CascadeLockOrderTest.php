<?php

declare(strict_types=1);

use App\Actions\Managers\EmployCurrentManagersAction;
use App\Actions\TagTeams\EmployCurrentWrestlersAction;
use App\Actions\TagTeams\ReinstateCurrentMembersAction;
use App\Actions\TagTeams\RetireCurrentMembersAction;
use App\Actions\TagTeams\SuspendCurrentMembersAction;
use App\Actions\TagTeams\SynchronizeMembershipAction;
use App\Actions\TagTeams\UnretireCurrentMembersAction;
use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Database\Factories\Roster\Managers\ManagerFactory;
use Database\Factories\Roster\Wrestlers\WrestlerFactory;
use Illuminate\Database\Eloquent\Collection;

/**
 * Each scenario describes the member state a cascade acts on and the action that runs it. Members are created in
 * ascending id order but attached to the tag team in a different order, so an unordered relationship query returns
 * them in pivot order instead of id order.
 *
 * @var array<string, array{0: Closure(): WrestlerFactory, 1: Closure(): ManagerFactory, 2: Closure(TagTeam): void}>
 */
$cascades = [
    'retire members' => [
        fn (): WrestlerFactory => Wrestler::factory()->employed(),
        fn (): ManagerFactory => Manager::factory()->employed(),
        fn (TagTeam $tagTeam) => resolve(RetireCurrentMembersAction::class)->handle($tagTeam, now()),
    ],
    'suspend members' => [
        fn (): WrestlerFactory => Wrestler::factory()->employed(),
        fn (): ManagerFactory => Manager::factory()->employed(),
        fn (TagTeam $tagTeam) => resolve(SuspendCurrentMembersAction::class)->handle($tagTeam, now()),
    ],
    'reinstate members' => [
        fn (): WrestlerFactory => Wrestler::factory()->suspended(),
        fn (): ManagerFactory => Manager::factory()->suspended(),
        fn (TagTeam $tagTeam) => resolve(ReinstateCurrentMembersAction::class)->handle($tagTeam, now()),
    ],
    'unretire members' => [
        fn (): WrestlerFactory => Wrestler::factory()->retired(),
        fn (): ManagerFactory => Manager::factory()->retired(),
        fn (TagTeam $tagTeam) => resolve(UnretireCurrentMembersAction::class)->handle($tagTeam, now()),
    ],
    'employ wrestlers' => [
        fn (): WrestlerFactory => Wrestler::factory()->unemployed(),
        fn (): ManagerFactory => Manager::factory()->unemployed(),
        fn (TagTeam $tagTeam) => resolve(EmployCurrentWrestlersAction::class)->handle($tagTeam, now()),
    ],
    'employ managers' => [
        fn (): WrestlerFactory => Wrestler::factory()->unemployed(),
        fn (): ManagerFactory => Manager::factory()->unemployed(),
        fn (TagTeam $tagTeam) => resolve(EmployCurrentManagersAction::class)->handle($tagTeam, now()),
    ],
];

dataset('tag team member cascades', $cascades);

test('it locks the current members of a tag team in ascending id order', function (Closure $wrestlerFactory, Closure $managerFactory, Closure $cascade) {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $wrestlers = $wrestlerFactory()->count(3)->create();
    $managers = $managerFactory()->count(3)->create();

    foreach ([2, 0, 1] as $position) {
        $tagTeam->wrestlers()->attach($wrestlers[$position], ['joined_at' => now()->subMonth()]);
        $tagTeam->managers()->attach($managers[$position], ['hired_at' => now()->subMonth()]);
    }

    // Act
    $statements = recordStatements(fn () => DB::transaction(fn () => $cascade($tagTeam)));

    // Assert
    $lockedWrestlerIds = lockedRowIds($statements, 'wrestlers');
    $lockedManagerIds = lockedRowIds($statements, 'managers');

    expect(array_merge($lockedWrestlerIds, $lockedManagerIds))->not->toBeEmpty()
        ->and($lockedWrestlerIds)->toBe(collect($lockedWrestlerIds)->sort()->values()->all())
        ->and($lockedManagerIds)->toBe(collect($lockedManagerIds)->sort()->values()->all());
})->with('tag team member cascades');

test('it ends replaced tag team members in ascending id order', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $wrestlers = Wrestler::factory()->count(3)->create();
    $managers = Manager::factory()->count(3)->create();

    foreach ([2, 0, 1] as $position) {
        $tagTeam->wrestlers()->attach($wrestlers[$position], ['joined_at' => now()->subMonth()]);
        $tagTeam->managers()->attach($managers[$position], ['hired_at' => now()->subMonth()]);
    }

    $tagTeam = TagTeam::query()->findOrFail($tagTeam->id);
    $members = new TagTeamMembershipData(wrestlers: new Collection, managers: new Collection);

    // Act
    $statements = recordStatements(fn () => resolve(SynchronizeMembershipAction::class)->handle($tagTeam, $members, now()));

    // Assert
    $unorderedMemberSelects = collect($statements)
        ->filter(fn (array $statement): bool => preg_match('/^select "(wrestlers|managers)"\.\*.*inner join/', $statement['sql']) === 1
            && preg_match('/order by "(wrestlers|managers)"\."id" asc/', $statement['sql']) !== 1);
    $endedWrestlerIds = updatedRowIds($statements, 'tag_teams_wrestlers');
    $endedManagerIds = updatedRowIds($statements, 'tag_teams_managers');

    expect($unorderedMemberSelects)->toBeEmpty()
        ->and($endedWrestlerIds)->toHaveCount(3)->toBe(collect($endedWrestlerIds)->sort()->values()->all())
        ->and($endedManagerIds)->toHaveCount(3)->toBe(collect($endedManagerIds)->sort()->values()->all());
});
