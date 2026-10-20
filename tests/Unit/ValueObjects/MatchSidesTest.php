<?php

declare(strict_types=1);

use App\Collections\TagTeamCollection;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\ValueObjects\MatchSides;

function wrestlerWithId(int $id): Wrestler
{
    return Wrestler::factory()
        ->make(['id' => $id]);
}

/** @param  list<Wrestler>  $members */
function tagTeamWithMembers(int $id, array $members): TagTeam
{
    $tagTeam = TagTeam::factory()
        ->make(['id' => $id]);
    $tagTeam->setRelation('currentWrestlers', (new Wrestler)->newCollection($members));

    return $tagTeam;
}

describe('building', function (): void {
    it('refuses a side key other than wrestlers or tag_teams', function (): void {
        // Arrange
        $sides = collect([['referees' => [wrestlerWithId(1)]]]);

        // Act
        $build = fn () => new MatchSides($sides);

        // Assert
        expect($build)->toThrow(InvalidArgumentException::class, 'A match side holds only wrestlers and tag_teams.');
    });

    it('refuses a tag team listed under wrestlers', function (): void {
        // Arrange
        $sides = collect([['wrestlers' => [tagTeamWithMembers(1, [])]]]);

        // Act
        $build = fn () => new MatchSides($sides);

        // Assert
        expect($build)->toThrow(InvalidArgumentException::class, 'Every wrestler on a side must be a wrestler.');
    });

    it('refuses a wrestler listed under tag_teams', function (): void {
        // Arrange
        $sides = collect([['tag_teams' => [wrestlerWithId(1)]]]);

        // Act
        $build = fn () => new MatchSides($sides);

        // Assert
        expect($build)->toThrow(InvalidArgumentException::class, 'Every tag team on a side must be a tag team.');
    });

    it('refuses a member that is neither a wrestler nor a tag team', function (): void {
        // Arrange
        $sides = collect([['wrestlers' => [Referee::factory()->make(['id' => 1])]]]);

        // Act
        $build = fn () => new MatchSides($sides);

        // Assert
        expect($build)->toThrow(InvalidArgumentException::class, 'Every wrestler on a side must be a wrestler.');
    });

    it('accepts a side with only one kind of member and an empty side', function (): void {
        // Arrange
        $sides = collect([
            1 => ['wrestlers' => [wrestlerWithId(1)]],
            2 => [],
        ]);

        // Act
        $matchSides = new MatchSides($sides);

        // Assert
        expect($matchSides->count())->toBe(2)
            ->and($matchSides->sides()->all())->toHaveKeys([1, 2])
            ->and($matchSides->sides()->get(2))->toBe(['wrestlers' => [], 'tag_teams' => []]);
    });
});

describe('reading', function (): void {
    it('knows whether it has any side', function (): void {
        // Arrange
        $empty = new MatchSides(collect());
        $filled = new MatchSides(collect([['wrestlers' => []]]));

        // Act
        $results = [$empty->isEmpty(), $filled->isEmpty()];

        // Assert
        expect($results)->toBe([true, false]);
    });

    it('keeps only the sides that have a competitor', function (): void {
        // Arrange
        $matchSides = new MatchSides(collect([
            1 => ['wrestlers' => [wrestlerWithId(1)]],
            2 => ['wrestlers' => []],
            3 => ['tag_teams' => [tagTeamWithMembers(1, [])]],
        ]));

        // Act
        $populated = $matchSides->populated();

        // Assert
        expect($populated->sides()->keys()->all())->toBe([1, 3])
            ->and($matchSides->count())->toBe(3);
    });

    it('lists wrestlers then tag teams of each side, side by side', function (): void {
        // Arrange
        $first = wrestlerWithId(1);
        $second = wrestlerWithId(2);
        $team = tagTeamWithMembers(7, []);
        $matchSides = new MatchSides(collect([
            ['tag_teams' => [$team], 'wrestlers' => [$first]],
            ['wrestlers' => [$second]],
        ]));

        // Act
        $competitors = $matchSides->competitors();

        // Assert
        expect($competitors->all())->toBe([$first, $team, $second])
            ->and($matchSides->wrestlers()->all())->toBe([$first, $second])
            ->and($matchSides->tagTeams())->toBeInstanceOf(TagTeamCollection::class)
            ->and($matchSides->tagTeams()->all())->toBe([$team]);
    });

    it('counts the competitor entries of the populated sides, smallest first', function (): void {
        // Arrange
        $matchSides = new MatchSides(collect([
            ['wrestlers' => [wrestlerWithId(1), wrestlerWithId(2)], 'tag_teams' => [tagTeamWithMembers(1, [])]],
            ['wrestlers' => []],
            ['wrestlers' => [wrestlerWithId(3)]],
        ]));

        // Act
        $entries = $matchSides->entriesPerSide();

        // Assert
        expect($entries)->toBe([1, 3]);
    });

    it('counts the roster members of the populated sides, smallest first, through the loaded team members', function (): void {
        // Arrange
        $team = tagTeamWithMembers(1, [wrestlerWithId(10), wrestlerWithId(11)]);
        $matchSides = new MatchSides(collect([
            ['tag_teams' => [$team], 'wrestlers' => [wrestlerWithId(1)]],
            ['wrestlers' => [wrestlerWithId(2)]],
            [],
        ]));

        // Act
        $members = $matchSides->rosterMembersPerSide();

        // Assert
        expect($members)->toBe([1, 3]);
    });
});
