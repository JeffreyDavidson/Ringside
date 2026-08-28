<?php

declare(strict_types=1);

use App\Data\Matches\MatchEliminationData;
use App\Data\Matches\MatchResultData;
use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Lifecycle\Matches\MatchOutcomeRequirements;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;

/**
 * @return array{MatchSide, MatchCompetitor}
 */
function createOutcomeCompetitor(EventMatch $match, int $position, ?int $entryOrder = null): array
{
    $side = MatchSide::factory()->for($match, 'match')->create([
        'position' => $position,
    ]);
    $competitor = MatchCompetitor::factory()->create([
        'match_id' => $match->id,
        'match_side_id' => $side->id,
    ]);
    $competitor->forceFill(['entry_order' => $entryOrder])->save();

    return [$side, $competitor];
}

/**
 * @param  list<MatchEliminationData>  $eliminations
 */
function ensureOutcomeRequirements(
    EventMatch $match,
    MatchFinish $finish,
    ?MatchSide $winningSide,
    array $eliminations = [],
): void {
    resolve(MatchOutcomeRequirements::class)->ensureSatisfied(
        $match,
        new MatchResultData($finish, $winningSide, collect($eliminations)),
        $match->competitors()->get(),
    );
}

it('accepts a decisive outcome with a populated winning side', function () {
    $match = EventMatch::factory()->create();
    [$winningSide] = createOutcomeCompetitor($match, 1);

    ensureOutcomeRequirements($match, MatchFinish::Pinfall, $winningSide);
})->throwsNoExceptions();

it('accepts a no-outcome finish without a winning side', function () {
    $match = EventMatch::factory()->create();

    ensureOutcomeRequirements($match, MatchFinish::NoDecision, null);
})->throwsNoExceptions();

it('requires a winning side for a decisive finish', function () {
    $match = EventMatch::factory()->create();

    ensureOutcomeRequirements($match, MatchFinish::Submission, null);
})->throws(InvalidMatchOutcomeException::class);

it('rejects a winning side for a no-outcome finish', function () {
    $match = EventMatch::factory()->create();
    [$winningSide] = createOutcomeCompetitor($match, 1);

    ensureOutcomeRequirements($match, MatchFinish::NoDecision, $winningSide);
})->throws(InvalidMatchOutcomeException::class);

it('requires the winning side to belong to the resulted match', function () {
    $match = EventMatch::factory()->create();
    $otherMatch = EventMatch::factory()->create();
    [$otherSide] = createOutcomeCompetitor($otherMatch, 1);

    ensureOutcomeRequirements($match, MatchFinish::Pinfall, $otherSide);
})->throws(InvalidMatchOutcomeException::class);

it('requires the winning side to contain a competitor from the locked snapshot', function () {
    $match = EventMatch::factory()->create();
    $emptySide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);

    ensureOutcomeRequirements($match, MatchFinish::Pinfall, $emptySide);
})->throws(InvalidMatchOutcomeException::class);

it('accepts complete elimination history', function () {
    $match = EventMatch::factory()->create(['match_type' => MatchType::BattleRoyal]);
    [, $firstEliminated] = createOutcomeCompetitor($match, 1);
    [, $secondEliminated] = createOutcomeCompetitor($match, 2);
    [$winningSide, $winner] = createOutcomeCompetitor($match, 3);

    ensureOutcomeRequirements($match, MatchFinish::Stipulation, $winningSide, [
        new MatchEliminationData($firstEliminated, 1, $winner),
        new MatchEliminationData($secondEliminated, 2, $winner),
    ]);
})->throwsNoExceptions();

it('accepts partial elimination history for a no-outcome finish', function () {
    $match = EventMatch::factory()->create(['match_type' => MatchType::BattleRoyal]);
    [, $eliminated] = createOutcomeCompetitor($match, 1);
    [, $eliminator] = createOutcomeCompetitor($match, 2);

    ensureOutcomeRequirements($match, MatchFinish::NoDecision, null, [
        new MatchEliminationData($eliminated, 1, $eliminator),
    ]);
})->throwsNoExceptions();

it('rejects elimination metadata for a match type that does not record it', function () {
    $match = EventMatch::factory()->create();
    [$winningSide, $competitor] = createOutcomeCompetitor($match, 1);

    ensureOutcomeRequirements($match, MatchFinish::Pinfall, $winningSide, [
        new MatchEliminationData($competitor, 1),
    ]);
})->throws(InvalidMatchOutcomeException::class);

it('rejects a duplicate eliminated competitor', function () {
    $match = EventMatch::factory()->create(['match_type' => MatchType::BattleRoyal]);
    [, $eliminated] = createOutcomeCompetitor($match, 1);
    [$winningSide, $winner] = createOutcomeCompetitor($match, 2);

    ensureOutcomeRequirements($match, MatchFinish::Stipulation, $winningSide, [
        new MatchEliminationData($eliminated, 1, $winner),
        new MatchEliminationData($eliminated, 2, $winner),
    ]);
})->throws(InvalidMatchOutcomeException::class);

it('rejects nonconsecutive elimination order', function () {
    $match = EventMatch::factory()->create(['match_type' => MatchType::BattleRoyal]);
    [, $eliminated] = createOutcomeCompetitor($match, 1);
    [$winningSide, $winner] = createOutcomeCompetitor($match, 2);

    ensureOutcomeRequirements($match, MatchFinish::Stipulation, $winningSide, [
        new MatchEliminationData($eliminated, 2, $winner),
    ]);
})->throws(InvalidMatchOutcomeException::class);

it('rejects an eliminated competitor from another match', function () {
    $match = EventMatch::factory()->create(['match_type' => MatchType::BattleRoyal]);
    [$winningSide, $winner] = createOutcomeCompetitor($match, 1);
    $otherMatch = EventMatch::factory()->create();
    [, $otherCompetitor] = createOutcomeCompetitor($otherMatch, 1);

    ensureOutcomeRequirements($match, MatchFinish::Stipulation, $winningSide, [
        new MatchEliminationData($otherCompetitor, 1, $winner),
    ]);
})->throws(InvalidMatchOutcomeException::class);

it('rejects an eliminating competitor from another match', function () {
    $match = EventMatch::factory()->create(['match_type' => MatchType::BattleRoyal]);
    [, $eliminated] = createOutcomeCompetitor($match, 1);
    [$winningSide] = createOutcomeCompetitor($match, 2);
    $otherMatch = EventMatch::factory()->create();
    [, $otherCompetitor] = createOutcomeCompetitor($otherMatch, 1);

    ensureOutcomeRequirements($match, MatchFinish::Stipulation, $winningSide, [
        new MatchEliminationData($eliminated, 1, $otherCompetitor),
    ]);
})->throws(InvalidMatchOutcomeException::class);

it('rejects self elimination', function () {
    $match = EventMatch::factory()->create(['match_type' => MatchType::BattleRoyal]);
    [, $eliminated] = createOutcomeCompetitor($match, 1);
    [$winningSide] = createOutcomeCompetitor($match, 2);

    ensureOutcomeRequirements($match, MatchFinish::Stipulation, $winningSide, [
        new MatchEliminationData($eliminated, 1, $eliminated),
    ]);
})->throws(InvalidMatchOutcomeException::class);

it('rejects an eliminated winner', function () {
    $match = EventMatch::factory()->create(['match_type' => MatchType::BattleRoyal]);
    [, $loser] = createOutcomeCompetitor($match, 1);
    [$winningSide, $winner] = createOutcomeCompetitor($match, 2);

    ensureOutcomeRequirements($match, MatchFinish::Stipulation, $winningSide, [
        new MatchEliminationData($winner, 1, $loser),
    ]);
})->throws(InvalidMatchOutcomeException::class);

it('requires every losing competitor in a decisive elimination match', function () {
    $match = EventMatch::factory()->create(['match_type' => MatchType::BattleRoyal]);
    [, $firstLoser] = createOutcomeCompetitor($match, 1);
    createOutcomeCompetitor($match, 2);
    [$winningSide, $winner] = createOutcomeCompetitor($match, 3);

    ensureOutcomeRequirements($match, MatchFinish::Stipulation, $winningSide, [
        new MatchEliminationData($firstLoser, 1, $winner),
    ]);
})->throws(InvalidMatchOutcomeException::class);

it('rejects an elimination credited after the eliminator exited', function () {
    $match = EventMatch::factory()->create(['match_type' => MatchType::BattleRoyal]);
    [, $firstCompetitor] = createOutcomeCompetitor($match, 1);
    [, $secondCompetitor] = createOutcomeCompetitor($match, 2);
    [$winningSide] = createOutcomeCompetitor($match, 3);

    ensureOutcomeRequirements($match, MatchFinish::Stipulation, $winningSide, [
        new MatchEliminationData($firstCompetitor, 1, $secondCompetitor),
        new MatchEliminationData($secondCompetitor, 2, $firstCompetitor),
    ]);
})->throws(InvalidMatchOutcomeException::class);

it('requires consecutive Royal Rumble entry order', function () {
    $match = EventMatch::factory()->create(['match_type' => MatchType::RoyalRumble]);
    [, $firstCompetitor] = createOutcomeCompetitor($match, 1, 1);
    [$winningSide, $winner] = createOutcomeCompetitor($match, 2, 3);

    ensureOutcomeRequirements($match, MatchFinish::Stipulation, $winningSide, [
        new MatchEliminationData($firstCompetitor, 1, $winner),
    ]);
})->throws(InvalidMatchOutcomeException::class);
