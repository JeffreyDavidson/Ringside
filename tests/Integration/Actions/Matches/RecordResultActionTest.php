<?php

declare(strict_types=1);

use App\Actions\Matches\RecordResultAction;
use App\Enums\MatchFinish;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;

function sideWithCompetitor(EventMatch $match, int $position): MatchSide
{
    $side = MatchSide::factory()->create([
        'match_id' => $match->id,
        'position' => $position,
    ]);

    MatchCompetitor::factory()->create([
        'match_id' => $match->id,
        'match_side_id' => $side->id,
    ]);

    return $side;
}

it('records a finish and winning side directly on the match', function () {
    $match = EventMatch::factory()->create();
    $winningSide = sideWithCompetitor($match, 1);

    $resultedMatch = resolve(RecordResultAction::class)->handle(
        $match,
        MatchFinish::Pinfall,
        $winningSide,
    );

    expect($resultedMatch->match_finish)->toBe(MatchFinish::Pinfall)
        ->and($resultedMatch->winningSide?->is($winningSide))->toBeTrue();
});

it('records a draw without a winning side', function () {
    $match = EventMatch::factory()->create();

    $resultedMatch = resolve(RecordResultAction::class)->handle(
        $match,
        MatchFinish::TimeLimitDraw,
        null,
    );

    expect($resultedMatch->match_finish)->toBe(MatchFinish::TimeLimitDraw)
        ->and($resultedMatch->winningSide)->toBeNull();
});

it('requires a winning side for a decisive finish', function () {
    $match = EventMatch::factory()->create();

    expect(fn () => resolve(RecordResultAction::class)->handle($match, MatchFinish::Submission, null))
        ->toThrow(InvalidMatchConfigurationException::class);
});

it('rejects a winning side for a no-outcome finish', function () {
    $match = EventMatch::factory()->create();
    $side = sideWithCompetitor($match, 1);

    expect(fn () => resolve(RecordResultAction::class)->handle($match, MatchFinish::NoDecision, $side))
        ->toThrow(InvalidMatchConfigurationException::class);
});

it('rejects a side belonging to another match', function () {
    $match = EventMatch::factory()->create();
    $otherMatch = EventMatch::factory()->create();
    $otherSide = sideWithCompetitor($otherMatch, 1);

    expect(fn () => resolve(RecordResultAction::class)->handle($match, MatchFinish::Pinfall, $otherSide))
        ->toThrow(InvalidMatchConfigurationException::class);
});

it('rejects an empty winning side', function () {
    $match = EventMatch::factory()->create();
    $emptySide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);

    expect(fn () => resolve(RecordResultAction::class)->handle($match, MatchFinish::Pinfall, $emptySide))
        ->toThrow(InvalidMatchConfigurationException::class);
});
