<?php

declare(strict_types=1);

use App\Actions\Matches\RecordResultAction;
use App\Data\Matches\MatchEliminationData;
use App\Data\Matches\MatchResultData;
use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;

function sideWithCompetitor(EventMatch $match, int $position, ?int $entryOrder = null): MatchSide
{
    $side = MatchSide::factory()->create([
        'match_id' => $match->id,
        'position' => $position,
    ]);

    $competitor = MatchCompetitor::factory()->create([
        'match_id' => $match->id,
        'match_side_id' => $side->id,
    ]);
    $competitor->forceFill(['entry_order' => $entryOrder])->save();

    return $side;
}

/**
 * @return array{EventMatch, list<MatchCompetitor>}
 */
function eliminationMatch(MatchType $matchType, int $competitorCount = 3): array
{
    $match = EventMatch::factory()->create(['match_type' => $matchType]);
    $competitors = [];

    foreach (range(1, $competitorCount) as $position) {
        $side = sideWithCompetitor(
            $match,
            $position,
            $matchType === MatchType::RoyalRumble ? $position : null,
        );
        $competitors[] = $side->competitors()->firstOrFail();
    }

    return [$match, $competitors];
}

/**
 * @param  array<int, MatchEliminationData>  $eliminations
 */
function matchResult(
    MatchFinish $finish,
    ?MatchSide $winningSide,
    array $eliminations = [],
): MatchResultData {
    return new MatchResultData($finish, $winningSide, collect($eliminations));
}

it('records an ordinary finish and winning side', function () {
    $match = EventMatch::factory()->create();
    $winningSide = sideWithCompetitor($match, 1);

    $resultedMatch = resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide),
    );

    expect($resultedMatch->match_finish)->toBe(MatchFinish::Pinfall)
        ->and($resultedMatch->winningSide?->is($winningSide))->toBeTrue();
});

it('records a draw without a winning side', function () {
    $match = EventMatch::factory()->create();

    $resultedMatch = resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::TimeLimitDraw, null),
    );

    expect($resultedMatch->match_finish)->toBe(MatchFinish::TimeLimitDraw)
        ->and($resultedMatch->winningSide)->toBeNull();
});

it('records a complete battle royal elimination history', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $winner = $competitors[2];
    $firstEliminated = $competitors[0];
    $secondEliminated = $competitors[1];

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($firstEliminated, 1, $winner),
            new MatchEliminationData($secondEliminated, 2, $winner),
        ]),
    );

    expect($firstEliminated->refresh()->elimination_order)->toBe(1)
        ->and($firstEliminated->eliminated_by_match_competitor_id)->toBe($winner->id)
        ->and($secondEliminated->refresh()->elimination_order)->toBe(2)
        ->and($secondEliminated->eliminated_by_match_competitor_id)->toBe($winner->id)
        ->and($winner->refresh()->elimination_order)->toBeNull();
});

it('records a royal rumble outcome with entry and elimination order', function () {
    [$match, $competitors] = eliminationMatch(MatchType::RoyalRumble);
    $winner = $competitors[2];

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($competitors[0], 1, $winner),
            new MatchEliminationData($competitors[1], 2, $winner),
        ]),
    );

    expect($match->competitors()->orderBy('entry_order')->pluck('entry_order')->all())->toBe([1, 2, 3])
        ->and($match->competitors()->orderBy('elimination_order')->pluck('elimination_order')->filter()->values()->all())
        ->toBe([1, 2]);
});

it('records a partial elimination history for a no-outcome elimination match', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);

    $resultedMatch = resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::NoDecision, null, [
            new MatchEliminationData($competitors[0], 1, $competitors[2]),
        ]),
    );

    expect($resultedMatch->match_finish)->toBe(MatchFinish::NoDecision)
        ->and($resultedMatch->winning_side_id)->toBeNull()
        ->and($competitors[0]->refresh()->elimination_order)->toBe(1)
        ->and($competitors[1]->refresh()->elimination_order)->toBeNull();
});

it('replaces a previously recorded outcome atomically', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $firstWinner = $competitors[2];
    $correctedWinner = $competitors[1];

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $firstWinner->side, [
            new MatchEliminationData($competitors[0], 1, $firstWinner),
            new MatchEliminationData($correctedWinner, 2, $firstWinner),
        ]),
    );

    $correctedMatch = resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Forfeit, $correctedWinner->side, [
            new MatchEliminationData($firstWinner, 1),
            new MatchEliminationData($competitors[0], 2, $correctedWinner),
        ]),
    );

    expect($correctedMatch->match_finish)->toBe(MatchFinish::Forfeit)
        ->and($correctedMatch->winning_side_id)->toBe($correctedWinner->match_side_id)
        ->and($firstWinner->refresh()->elimination_order)->toBe(1)
        ->and($firstWinner->eliminated_by_match_competitor_id)->toBeNull()
        ->and($correctedWinner->refresh()->elimination_order)->toBeNull();
});

it('rolls back an invalid correction without changing the recorded outcome', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $winner = $competitors[2];

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($competitors[0], 1, $winner),
            new MatchEliminationData($competitors[1], 2, $winner),
        ]),
    );

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::NoDecision, null, [
            new MatchEliminationData($competitors[0], 2),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class);

    expect($match->refresh()->match_finish)->toBe(MatchFinish::Stipulation)
        ->and($match->winning_side_id)->toBe($winner->match_side_id)
        ->and($competitors[0]->refresh()->elimination_order)->toBe(1)
        ->and($competitors[1]->refresh()->elimination_order)->toBe(2);
});

it('requires a winning side for a decisive finish', function () {
    $match = EventMatch::factory()->create();

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Submission, null),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects a winning side for a no-outcome finish', function () {
    $match = EventMatch::factory()->create();
    $side = sideWithCompetitor($match, 1);

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::NoDecision, $side),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects a side belonging to another match', function () {
    $match = EventMatch::factory()->create();
    $otherMatch = EventMatch::factory()->create();
    $otherSide = sideWithCompetitor($otherMatch, 1);

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $otherSide),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects an empty winning side', function () {
    $match = EventMatch::factory()->create();
    $emptySide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $emptySide),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects elimination metadata for an ordinary match', function () {
    $match = EventMatch::factory()->create();
    $winningSide = sideWithCompetitor($match, 1);
    $competitor = $match->competitors()->firstOrFail();

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide, [
            new MatchEliminationData($competitor, 1),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('requires every losing competitor in a decisive elimination match', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $winner = $competitors[2];

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($competitors[0], 1, $winner),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects eliminating the winning competitor', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $winner = $competitors[2];

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($competitors[0], 1, $winner),
            new MatchEliminationData($winner, 2, $competitors[1]),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects an eliminated competitor from another match', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $otherMatch = EventMatch::factory()->create();
    $otherCompetitor = sideWithCompetitor($otherMatch, 1)->competitors()->firstOrFail();
    $winner = $competitors[2];

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($otherCompetitor, 1, $winner),
            new MatchEliminationData($competitors[1], 2, $winner),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects a self elimination', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $winner = $competitors[2];

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($competitors[0], 1, $competitors[0]),
            new MatchEliminationData($competitors[1], 2, $winner),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects a royal rumble with invalid entry order', function () {
    [$match, $competitors] = eliminationMatch(MatchType::RoyalRumble);
    $competitors[1]->forceFill(['entry_order' => null])->save();
    $winner = $competitors[2];

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($competitors[0], 1, $winner),
            new MatchEliminationData($competitors[1], 2, $winner),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects an elimination credited after the eliminator exited', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $winner = $competitors[2];

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($competitors[0], 1, $competitors[1]),
            new MatchEliminationData($competitors[1], 2, $competitors[0]),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class);
});
