<?php

declare(strict_types=1);

use App\Enums\Shared\BusinessRuleReason;
use App\Enums\Titles\TitleType;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Lifecycle\Matches\MatchTitleRequirements;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

/**
 * Builds a match whose sides each hold the given number of wrestlers or tag teams.
 *
 * @param  array<int, int>  $competitorsPerSide
 */
function matchWithCompetitorsPerSide(array $competitorsPerSide, TitleType $competitorKind): EventMatch
{
    $match = EventMatch::factory()->create();

    foreach ($competitorsPerSide as $index => $count) {
        $side = MatchSide::factory()
            ->for($match, 'match')
            ->create(['position' => $index + 1]);

        foreach (range(1, $count) as $ignored) {
            $competitor = $competitorKind === TitleType::Singles
                ? Wrestler::factory()->bookable()->create()
                : TagTeam::factory()->bookable()->create();

            MatchCompetitor::factory()
                ->for($match, 'eventMatch')
                ->for($side, 'side')
                ->create([
                    'competitor_type' => $competitor->getMorphClass(),
                    'competitor_id' => $competitor->id,
                ]);
        }
    }

    return $match;
}

it('refuses a title when a side holds more than one competitor of the title type', function (TitleType $type, array $competitorsPerSide) {
    // Arrange
    $match = matchWithCompetitorsPerSide($competitorsPerSide, $type);
    $title = Title::factory()
        ->active()
        ->create(['type' => $type]);

    // Act
    $attempt = fn () => resolve(MatchTitleRequirements::class)->ensureSatisfied($match, collect([$title]));

    // Assert
    expect($attempt)
        ->toThrow(InvalidMatchConfigurationException::class, "The [{$title->name}] needs exactly one competitor of its type on each side.");
})->with([
    'handicap with a singles title' => [TitleType::Singles, [2, 1]],
    'six-man tag with a singles title' => [TitleType::Singles, [3, 3]],
    'ten-man tag with a singles title' => [TitleType::Singles, [5, 5]],
    'eight-man tag with two teams per side and a tag title' => [TitleType::TagTeam, [2, 2]],
]);

it('reports the one-competitor-per-side reason', function () {
    // Arrange
    $match = matchWithCompetitorsPerSide([2, 1], TitleType::Singles);
    $title = Title::factory()
        ->active()
        ->create(['type' => TitleType::Singles]);
    $reason = null;

    // Act
    try {
        resolve(MatchTitleRequirements::class)->ensureSatisfied($match, collect([$title]));
    } catch (InvalidMatchConfigurationException $exception) {
        $reason = $exception->reason();
    }

    // Assert
    expect($reason)->toBe(BusinessRuleReason::TitleRequiresOneCompetitorPerSide);
});

it('allows a title when every populated side holds exactly one competitor of its type', function (TitleType $type, array $competitorsPerSide) {
    // Arrange
    $match = matchWithCompetitorsPerSide($competitorsPerSide, $type);
    $title = Title::factory()
        ->active()
        ->create(['type' => $type]);

    // Act
    resolve(MatchTitleRequirements::class)->ensureSatisfied($match, collect([$title]));

    // Assert
    expect($match->competitors()->count())->toBe(array_sum($competitorsPerSide));
})->with([
    'singles match' => [TitleType::Singles, [1, 1]],
    'triple threat' => [TitleType::Singles, [1, 1, 1]],
    'fatal four-way' => [TitleType::Singles, [1, 1, 1, 1]],
    'tag team match' => [TitleType::TagTeam, [1, 1]],
]);
