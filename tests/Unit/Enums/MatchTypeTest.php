<?php

declare(strict_types=1);

use App\Enums\MatchType;

it('defines roster-member compositions for formats that encode side sizes', function (
    MatchType $matchType,
    array $composition,
) {
    expect($matchType->requiredRosterMembersPerSide())->toBe($composition);
})->with([
    'tag team' => [MatchType::TagTeam, [2, 2]],
    'six man tag team' => [MatchType::SixManTagTeam, [3, 3]],
    'eight man tag team' => [MatchType::EightManTagTeam, [4, 4]],
    'ten man tag team' => [MatchType::TenManTagTeam, [5, 5]],
    'two on one handicap' => [MatchType::TwoOnOneHandicap, [1, 2]],
    'three on two handicap' => [MatchType::ThreeOnTwoHandicap, [2, 3]],
    'tornado tag team' => [MatchType::TornadoTagTeam, [2, 2]],
]);

it('allows mixed competitors for flexible match formats', function (MatchType $matchType) {
    expect($matchType->allowsWrestlers())->toBeTrue()
        ->and($matchType->allowsTagTeams())->toBeTrue();
})->with([
    MatchType::TagTeam,
    MatchType::TripleThreat,
    MatchType::Fatal4Way,
    MatchType::SixManTagTeam,
    MatchType::EightManTagTeam,
    MatchType::TenManTagTeam,
    MatchType::TwoOnOneHandicap,
    MatchType::ThreeOnTwoHandicap,
    MatchType::TornadoTagTeam,
    MatchType::Gauntlet,
]);

it('defines one-entry sides for independently competing formats', function (MatchType $matchType, array $composition) {
    expect($matchType->requiredCompetitorEntriesPerSide())->toBe($composition);
})->with([
    'singles' => [MatchType::Singles, [1, 1]],
    'triple threat' => [MatchType::TripleThreat, [1, 1, 1]],
    'triangle' => [MatchType::Triangle, [1, 1, 1]],
    'fatal four way' => [MatchType::Fatal4Way, [1, 1, 1, 1]],
]);

it('restricts individual-only match formats to wrestlers', function (MatchType $matchType) {
    expect($matchType->allowsWrestlers())->toBeTrue()
        ->and($matchType->allowsTagTeams())->toBeFalse();
})->with([
    MatchType::Singles,
    MatchType::Triangle,
    MatchType::BattleRoyal,
    MatchType::RoyalRumble,
]);
