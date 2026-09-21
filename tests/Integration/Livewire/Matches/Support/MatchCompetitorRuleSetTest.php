<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Livewire\Matches\Support\MatchCompetitorRuleSet;

describe('match competitor validation rules', function (): void {
    it('builds fixed individual-side rules for :dataset matches', function (MatchType $matchType, int $sideCount): void {
        // Arrange
        $ruleSet = new MatchCompetitorRuleSet($matchType);

        // Act
        $rules = $ruleSet->rules();

        // Assert
        expect($rules['competitors'])->toContain("size:{$sideCount}");
        expect($rules['competitors.*.wrestlers.*'])->toContain('distinct');

        foreach (range(0, $sideCount - 1) as $sideIndex) {
            expect($rules["competitors.{$sideIndex}.wrestlers"])->toContain('size:1');
        }
    })->with([
        'singles' => [MatchType::Singles, 2],
        'triangle' => [MatchType::Triangle, 3],
    ]);

    it('builds mutually exclusive mixed-competitor rules for :dataset matches', function (MatchType $matchType, int $sideCount): void {
        // Arrange
        $ruleSet = new MatchCompetitorRuleSet($matchType);

        // Act
        $rules = $ruleSet->rules();

        // Assert
        expect($rules['competitors'])->toContain("size:{$sideCount}")
            ->and($rules['competitors.*.wrestlers.*'])->toContain('distinct')
            ->and($rules['competitors.*.tag_teams.*'])->toContain('distinct');

        foreach (range(0, $sideCount - 1) as $sideIndex) {
            expect($rules["competitors.{$sideIndex}.wrestlers"])->toContain('max:1')
                ->and($rules["competitors.{$sideIndex}.tag_teams"])->toContain('max:1');
        }
    })->with([
        'triple threat' => [MatchType::TripleThreat, 3],
        'fatal four way' => [MatchType::Fatal4Way, 4],
    ]);

    it('builds tag team selection rules for :dataset matches', function (MatchType $matchType): void {
        // Arrange
        $ruleSet = new MatchCompetitorRuleSet($matchType);

        // Act
        $rules = $ruleSet->rules();

        // Assert
        expect($rules['competitors'])->toContain('size:2')
            ->and($rules['competitors.*.wrestlers.*'])->toContain('distinct')
            ->and($rules['competitors.*.tag_teams.*'])->toContain('distinct')
            ->and($rules['competitors.0.wrestlers'])->toContain('min:2')
            ->and($rules['competitors.0.tag_teams'])->toContain('min:1')
            ->and($rules['competitors.1.wrestlers'])->toContain('min:2')
            ->and($rules['competitors.1.tag_teams'])->toContain('min:1');
    })->with([
        'tag team' => MatchType::TagTeam,
        'six-man tag team' => MatchType::SixManTagTeam,
        'eight-man tag team' => MatchType::EightManTagTeam,
        'ten-man tag team' => MatchType::TenManTagTeam,
        'tornado tag team' => MatchType::TornadoTagTeam,
    ]);

    it('builds individual entrant limits for :dataset matches', function (MatchType $matchType, string $minimum, ?string $maximum): void {
        // Arrange
        $ruleSet = new MatchCompetitorRuleSet($matchType);

        // Act
        $rules = $ruleSet->rules();

        // Assert
        expect($rules['competitors'])->toContain('size:1')
            ->and($rules['competitors.0.wrestlers'])->toContain($minimum)
            ->and($rules['competitors.0.wrestlers.*'])->toContain('distinct');

        if ($maximum === null) {
            expect($rules['competitors.0.wrestlers'])->not->toContain('max:30');

            return;
        }

        expect($rules['competitors.0.wrestlers'])->toContain($maximum);
    })->with([
        'battle royal' => [MatchType::BattleRoyal, 'min:3', null],
        'royal rumble' => [MatchType::RoyalRumble, 'min:10', 'max:30'],
    ]);

    it('uses permissive nested rules until a match type is selected', function (): void {
        // Arrange
        $ruleSet = new MatchCompetitorRuleSet(null);

        // Act
        $rules = $ruleSet->rules();

        // Assert
        expect($rules['competitors'])->toContain('sometimes')
            ->and($rules)->toHaveKeys([
                'competitors.*.wrestlers',
                'competitors.*.wrestlers.*',
                'competitors.*.tag_teams',
                'competitors.*.tag_teams.*',
            ]);
    });

    it('builds fixed mixed-competitor rules for :dataset matches', function (MatchType $matchType): void {
        // Arrange
        $ruleSet = new MatchCompetitorRuleSet($matchType);

        // Act
        $rules = $ruleSet->rules();

        // Assert
        expect($rules['competitors'])->toContain('size:2')
            ->and($rules['competitors.*.wrestlers.*'])->toContain('distinct')
            ->and($rules['competitors.*.tag_teams.*'])->toContain('distinct')
            ->and($rules)->toHaveKeys([
                'competitors.*.wrestlers',
                'competitors.*.tag_teams',
            ]);
    })->with([
        'two-on-one handicap' => MatchType::TwoOnOneHandicap,
        'three-on-two handicap' => MatchType::ThreeOnTwoHandicap,
        'gauntlet' => MatchType::Gauntlet,
    ]);
});
