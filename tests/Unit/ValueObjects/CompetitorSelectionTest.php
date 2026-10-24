<?php

declare(strict_types=1);

use App\Enums\Matches\MatchType;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\ValueObjects\CompetitorSelection;
use Illuminate\Database\Eloquent\Relations\Relation;

describe('building from the match form sides', function (): void {
    it('numbers the entries by the side they were chosen on', function (): void {
        // Arrange
        $wrestler = Relation::getMorphAlias(Wrestler::class);
        $team = Relation::getMorphAlias(TagTeam::class);
        $sides = [
            ['wrestlers' => [4, 5], 'tag_teams' => [9]],
            ['wrestlers' => [6]],
        ];

        // Act
        $selection = CompetitorSelection::fromSides(MatchType::Singles, $sides);

        // Assert
        expect($selection->entries())->toBe([
            ['type' => $wrestler, 'id' => 4, 'side' => 0],
            ['type' => $wrestler, 'id' => 5, 'side' => 0],
            ['type' => $team, 'id' => 9, 'side' => 0],
            ['type' => $wrestler, 'id' => 6, 'side' => 1],
        ]);
    });

    it('gives each competitor of an individual-sides match its own side', function (): void {
        // Arrange
        $wrestler = Relation::getMorphAlias(Wrestler::class);
        $team = Relation::getMorphAlias(TagTeam::class);
        $sides = [['wrestlers' => [4, 5], 'tag_teams' => [9]]];

        // Act
        $selection = CompetitorSelection::fromSides(MatchType::BattleRoyal, $sides);

        // Assert
        expect($selection->entries())->toBe([
            ['type' => $wrestler, 'id' => 4, 'side' => 0],
            ['type' => $wrestler, 'id' => 5, 'side' => 1],
            ['type' => $team, 'id' => 9, 'side' => 2],
        ]);
    });

    it('casts string ids to integers', function (): void {
        // Arrange
        $sides = [['wrestlers' => ['7']], ['wrestlers' => ['8']]];

        // Act
        $selection = CompetitorSelection::fromSides(MatchType::Singles, $sides);

        // Assert
        expect($selection->entries()[0]['id'])->toBe(7);
    });
});

describe('reading the selection', function (): void {
    it('groups the ids by type in the order the types first appear', function (): void {
        // Arrange
        $wrestler = Relation::getMorphAlias(Wrestler::class);
        $team = Relation::getMorphAlias(TagTeam::class);
        $selection = new CompetitorSelection([
            ['type' => $team, 'id' => 3, 'side' => 0],
            ['type' => $wrestler, 'id' => 1, 'side' => 1],
            ['type' => $team, 'id' => 2, 'side' => 1],
        ]);

        // Act
        $ids = $selection->idsByType();

        // Assert
        expect($ids)->toBe([$team => [3, 2], $wrestler => [1]])
            ->and(array_keys($ids))->toBe([$team, $wrestler]);
    });

    it('lists each side once in the order the sides first appear', function (): void {
        // Arrange
        $wrestler = Relation::getMorphAlias(Wrestler::class);
        $selection = new CompetitorSelection([
            ['type' => $wrestler, 'id' => 1, 'side' => 2],
            ['type' => $wrestler, 'id' => 2, 'side' => 0],
            ['type' => $wrestler, 'id' => 3, 'side' => 2],
        ]);

        // Act
        $sides = $selection->sides();

        // Assert
        expect($sides)->toBe([2, 0]);
    });

    it('splits into the competitors on a side and the competitors facing it', function (): void {
        // Arrange
        $wrestler = Relation::getMorphAlias(Wrestler::class);
        $team = Relation::getMorphAlias(TagTeam::class);
        $selection = new CompetitorSelection([
            ['type' => $wrestler, 'id' => 1, 'side' => 0],
            ['type' => $team, 'id' => 2, 'side' => 1],
            ['type' => $wrestler, 'id' => 3, 'side' => 0],
        ]);

        // Act
        $on = $selection->onSide(0);
        $off = $selection->offSide(0);

        // Assert
        expect($on->entries())->toBe([
            ['type' => $wrestler, 'id' => 1, 'side' => 0],
            ['type' => $wrestler, 'id' => 3, 'side' => 0],
        ])
            ->and($off->entries())->toBe([['type' => $team, 'id' => 2, 'side' => 1]]);
    });

    it('has nothing facing the only side', function (): void {
        // Arrange
        $selection = new CompetitorSelection([['type' => Relation::getMorphAlias(Wrestler::class), 'id' => 1, 'side' => 0]]);

        // Act
        $off = $selection->offSide(0);

        // Assert
        expect($off->entries())->toBeEmpty()
            ->and($off->idsByType())->toBeEmpty()
            ->and($off->sides())->toBeEmpty();
    });

    it('is head to head only for two competitors of one type on different sides', function (array $chosen, bool $expected): void {
        // Arrange
        $aliases = [
            'wrestler' => Relation::getMorphAlias(Wrestler::class),
            'team' => Relation::getMorphAlias(TagTeam::class),
        ];
        $selection = new CompetitorSelection(array_map(
            fn (array $entry): array => ['type' => $aliases[$entry[0]], 'id' => $entry[1], 'side' => $entry[2]],
            $chosen,
        ));

        // Act
        $headToHead = $selection->isHeadToHead();

        // Assert
        expect($headToHead)->toBe($expected);
    })->with([
        'two wrestlers on different sides' => [[['wrestler', 1, 0], ['wrestler', 2, 1]], true],
        'two teams on different sides' => [[['team', 1, 0], ['team', 2, 1]], true],
        'two wrestlers on the same side' => [[['wrestler', 1, 0], ['wrestler', 2, 0]], false],
        'a wrestler against a team' => [[['wrestler', 1, 0], ['team', 2, 1]], false],
        'three competitors' => [[['wrestler', 1, 0], ['wrestler', 2, 1], ['wrestler', 3, 2]], false],
        'one competitor' => [[['wrestler', 1, 0]], false],
        'nobody' => [[], false],
    ]);
});

describe('refusing an invalid entry', function (): void {
    it('refuses a type that is not a roster competitor', function (): void {
        // Arrange
        $entries = [['type' => 'referee', 'id' => 1, 'side' => 0]];

        // Act
        $build = fn (): CompetitorSelection => new CompetitorSelection($entries);

        // Assert
        expect($build)->toThrow(InvalidArgumentException::class);
    });

    it('refuses an entry that is missing a key', function (string $missing): void {
        // Arrange
        $entry = ['type' => Relation::getMorphAlias(Wrestler::class), 'id' => 1, 'side' => 0];
        unset($entry[$missing]);

        // Act
        $build = fn (): CompetitorSelection => new CompetitorSelection([$entry]);

        // Assert
        expect($build)->toThrow(InvalidArgumentException::class);
    })->with(['type', 'id', 'side']);

    it('refuses an id or side that is not an integer', function (string $key, mixed $value): void {
        // Arrange
        $entry = ['type' => Relation::getMorphAlias(Wrestler::class), 'id' => 1, 'side' => 0];
        $entry[$key] = $value;

        // Act
        $build = fn (): CompetitorSelection => new CompetitorSelection([$entry]);

        // Assert
        expect($build)->toThrow(InvalidArgumentException::class);
    })->with([
        'string id' => ['id', '1'],
        'float id' => ['id', 1.5],
        'string side' => ['side', '0'],
        'null side' => ['side', null],
    ]);
});

describe('building from unvalidated form input', function (): void {
    it('numbers array sides in order, skips malformed lists and counts a non-id entry as id 0', function (): void {
        // Arrange
        $wrestler = Relation::getMorphAlias(Wrestler::class);
        $team = Relation::getMorphAlias(TagTeam::class);
        $input = ['not-a-side', ['wrestlers' => 'x'], ['wrestlers' => ['4', null], 'tag_teams' => [9]], ['wrestlers' => [6]]];

        // Act
        $selection = CompetitorSelection::fromInput($input);

        // Assert
        expect($selection->entries())->toBe([
            ['type' => $wrestler, 'id' => 4, 'side' => 1],
            ['type' => $wrestler, 'id' => 0, 'side' => 1],
            ['type' => $team, 'id' => 9, 'side' => 1],
            ['type' => $wrestler, 'id' => 6, 'side' => 2],
        ]);
    });

    it('reads input that is not a list of sides as no competitors', function (): void {
        // Arrange
        $input = 'wrestlers';

        // Act
        $selection = CompetitorSelection::fromInput($input);

        // Assert
        expect($selection->entries())->toBeEmpty();
    });
});
