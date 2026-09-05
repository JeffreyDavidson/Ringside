<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Livewire\Matches\Enums\CompetitorSelectionLayout;

describe('competitor selection layouts', function (): void {
    it('maps :dataset matches to their selection layout', function (
        MatchType $matchType,
        CompetitorSelectionLayout $expectedLayout,
    ): void {
        // Arrange

        // Act
        $layout = CompetitorSelectionLayout::forMatchType($matchType);

        // Assert
        expect($layout)->toBe($expectedLayout);
    })->with([
        'singles' => [MatchType::Singles, CompetitorSelectionLayout::Singles],
        'tag team' => [MatchType::TagTeam, CompetitorSelectionLayout::TagTeam],
        'six-man tag team' => [MatchType::SixManTagTeam, CompetitorSelectionLayout::TagTeam],
        'eight-man tag team' => [MatchType::EightManTagTeam, CompetitorSelectionLayout::TagTeam],
        'ten-man tag team' => [MatchType::TenManTagTeam, CompetitorSelectionLayout::TagTeam],
        'tornado tag team' => [MatchType::TornadoTagTeam, CompetitorSelectionLayout::TagTeam],
        'triple threat' => [MatchType::TripleThreat, CompetitorSelectionLayout::TripleThreat],
        'triangle' => [MatchType::Triangle, CompetitorSelectionLayout::TripleThreat],
        'fatal four way' => [MatchType::Fatal4Way, CompetitorSelectionLayout::FatalFourWay],
        'battle royal' => [MatchType::BattleRoyal, CompetitorSelectionLayout::BattleRoyal],
        'royal rumble' => [MatchType::RoyalRumble, CompetitorSelectionLayout::BattleRoyal],
        'two-on-one handicap' => [MatchType::TwoOnOneHandicap, CompetitorSelectionLayout::Generic],
        'three-on-two handicap' => [MatchType::ThreeOnTwoHandicap, CompetitorSelectionLayout::Generic],
        'gauntlet' => [MatchType::Gauntlet, CompetitorSelectionLayout::Generic],
    ]);
});
