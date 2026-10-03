<?php

declare(strict_types=1);

use App\Actions\Matches\RecordResultAction;
use App\Data\Matches\MatchResultData;
use App\Enums\MatchFinish;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\Wrestlers\Wrestler;

/**
 * Record a pinfall for the given side and return the statements it issued.
 *
 * @return array<int, array{sql: string, bindings: array<int, mixed>, locked: bool}>
 */
function recordResultStatements(EventMatch $match, MatchSide $winningSide): array
{
    return recordStatements(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        new MatchResultData(MatchFinish::Pinfall, $winningSide, collect()),
    ));
}

/**
 * Position of the first statement that writes the result onto the match.
 *
 * @param  array<int, array{sql: string, bindings: array<int, mixed>, locked: bool}>  $statements
 */
function resultWritePosition(array $statements): int
{
    return array_find_key($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'update "events_matches" '))
        ?? throw new RuntimeException('Expected the result to be written to the match.');
}

describe('result recording locks', function (): void {
    it('locks the winning side row before writing the result', function (): void {
        // Arrange
        $match = EventMatch::factory()->create();
        [$winningSide, $losingSide] = MatchSide::factory()->for($match, 'match')->count(2)
            ->sequence(['position' => 1], ['position' => 2])
            ->create()
            ->all();
        foreach ([$winningSide, $losingSide] as $side) {
            MatchCompetitor::factory()->for($match, 'eventMatch')->for($side, 'side')->for(Wrestler::factory(), 'competitor')->create();
        }

        // Act
        $statements = recordResultStatements($match, $winningSide);

        // Assert
        $sideLock = array_find_key(
            $statements,
            fn (array $statement): bool => $statement['locked']
                && str_contains($statement['sql'], 'from "events_matches_sides"')
                && boundKey($statement) === $winningSide->id,
        );

        expect($sideLock)->not->toBeNull()
            ->and($sideLock)->toBeLessThan(resultWritePosition($statements))
            ->and(lockedRowIds($statements, 'events_matches_sides'))->not->toContain($losingSide->id);
    });

    it('locks every competitor of the match in ascending id order before writing the result', function (): void {
        // Arrange
        $match = EventMatch::factory()->create();
        [$firstSide, $secondSide] = MatchSide::factory()->for($match, 'match')->count(2)
            ->sequence(['position' => 1], ['position' => 2])
            ->create()
            ->all();
        foreach ([$secondSide, $firstSide] as $side) {
            MatchCompetitor::factory()->for($match, 'eventMatch')->for($side, 'side')->for(Wrestler::factory(), 'competitor')->create();
        }

        // Act
        $statements = recordResultStatements($match, $firstSide);

        // Assert
        $competitorLock = array_find_key(
            $statements,
            fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "events_matches_competitors"'),
        );

        expect($competitorLock)->not->toBeNull()
            ->and($statements[$competitorLock]['sql'])->toContain('order by "id" asc')
            ->and($statements[$competitorLock]['bindings'])->toBe([$match->id])
            ->and($competitorLock)->toBeLessThan(resultWritePosition($statements));
    });
});
