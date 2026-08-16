<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Livewire\Matches\Enums\CompetitorSelectionLayout;

it('maps match types to their competitor selection layout', function (
    MatchType $matchType,
    CompetitorSelectionLayout $layout,
) {
    expect(CompetitorSelectionLayout::forMatchType($matchType))->toBe($layout);
})->with([
    [MatchType::Singles, CompetitorSelectionLayout::Singles],
    [MatchType::TagTeam, CompetitorSelectionLayout::TagTeam],
    [MatchType::SixManTagTeam, CompetitorSelectionLayout::TagTeam],
    [MatchType::EightManTagTeam, CompetitorSelectionLayout::TagTeam],
    [MatchType::TenManTagTeam, CompetitorSelectionLayout::TagTeam],
    [MatchType::TornadoTagTeam, CompetitorSelectionLayout::TagTeam],
    [MatchType::TripleThreat, CompetitorSelectionLayout::TripleThreat],
    [MatchType::Triangle, CompetitorSelectionLayout::TripleThreat],
    [MatchType::Fatal4Way, CompetitorSelectionLayout::FatalFourWay],
    [MatchType::BattleRoyal, CompetitorSelectionLayout::BattleRoyal],
    [MatchType::RoyalRumble, CompetitorSelectionLayout::BattleRoyal],
    [MatchType::TwoOnOneHandicap, CompetitorSelectionLayout::Generic],
    [MatchType::ThreeOnTwoHandicap, CompetitorSelectionLayout::Generic],
    [MatchType::Gauntlet, CompetitorSelectionLayout::Generic],
]);
