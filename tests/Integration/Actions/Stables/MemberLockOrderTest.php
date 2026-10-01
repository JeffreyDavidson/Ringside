<?php

declare(strict_types=1);

use App\Actions\Stables\RetireAction;
use App\Actions\Stables\SynchronizeStableMembersAction;
use App\Data\Stables\StableMembershipData;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

/**
 * The ordering of the queries that load the members a cascade iterates. SQLite may return the rows of a join in
 * primary key order whatever the query says, so the statement itself is asserted as well as the resulting order.
 *
 * @param  array<int, array{sql: string, bindings: array<int, mixed>, locked: bool}>  $statements
 * @return array<int, string>
 */
function memberSelectsWithoutOrdering(array $statements, string $memberTable, string $pivotTable): array
{
    $unordered = [];

    foreach ($statements as $statement) {
        if (
            str_starts_with($statement['sql'], "select \"{$memberTable}\".*")
            && str_contains($statement['sql'], "inner join \"{$pivotTable}\"")
            && ! str_contains($statement['sql'], "order by \"{$memberTable}\".\"id\" asc")
        ) {
            $unordered[] = $statement['sql'];
        }
    }

    return $unordered;
}

/**
 * Members are created in ascending id order but attached in a different order, so an unordered relationship query
 * returns them in pivot order instead of id order.
 */
function stableWithMembersAttachedOutOfOrder(): Stable
{
    $stable = Stable::factory()->active()->create();
    $stable->wrestlers()->detach();
    $stable->tagTeams()->detach();

    $wrestlers = Wrestler::factory()->employed()->count(3)->create();
    $tagTeams = TagTeam::factory()->employed()->count(3)->create();

    foreach ([2, 0, 1] as $position) {
        $stable->wrestlers()->attach($wrestlers[$position], ['joined_at' => now()->subMonth()]);
        $stable->tagTeams()->attach($tagTeams[$position], ['joined_at' => now()->subMonth()]);
    }

    return Stable::query()->findOrFail($stable->id);
}

test('it retires the members of a stable in ascending id order', function () {
    // Arrange
    $stable = stableWithMembersAttachedOutOfOrder();

    // Act
    $statements = recordStatements(fn () => resolve(RetireAction::class)->handle($stable));

    // Assert
    $lockedWrestlerIds = lockedRowIds($statements, 'wrestlers');
    $lockedTagTeamIds = lockedRowIds($statements, 'tag_teams');

    expect($lockedWrestlerIds)->not->toBeEmpty()
        ->and($lockedTagTeamIds)->not->toBeEmpty()
        ->and($lockedWrestlerIds)->toBe(collect($lockedWrestlerIds)->sort()->values()->all())
        ->and($lockedTagTeamIds)->toBe(collect($lockedTagTeamIds)->sort()->values()->all())
        ->and(memberSelectsWithoutOrdering($statements, 'wrestlers', 'stables_wrestlers'))->toBeEmpty()
        ->and(memberSelectsWithoutOrdering($statements, 'tag_teams', 'stables_tag_teams'))->toBeEmpty();
});

test('it removes replaced stable members in ascending id order', function () {
    // Arrange
    $stable = stableWithMembersAttachedOutOfOrder();
    $members = new StableMembershipData(wrestlers: collect(), tagTeams: collect());

    // Act
    $statements = recordStatements(fn () => resolve(SynchronizeStableMembersAction::class)->handle($stable, $members, now()));

    // Assert
    $removedWrestlerIds = updatedRowIds($statements, 'stables_wrestlers');
    $removedTagTeamIds = updatedRowIds($statements, 'stables_tag_teams');

    expect($removedWrestlerIds)->toHaveCount(3)->toBe(collect($removedWrestlerIds)->sort()->values()->all())
        ->and($removedTagTeamIds)->toHaveCount(3)->toBe(collect($removedTagTeamIds)->sort()->values()->all())
        ->and(memberSelectsWithoutOrdering($statements, 'wrestlers', 'stables_wrestlers'))->toBeEmpty()
        ->and(memberSelectsWithoutOrdering($statements, 'tag_teams', 'stables_tag_teams'))->toBeEmpty();
});
