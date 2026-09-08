<?php

declare(strict_types=1);

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

it('groups competitor models by ordered side position', function () {
    // Arrange
    $match = EventMatch::factory()->create();
    $firstSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    $secondSide = MatchSide::factory()->for($match, 'match')->create(['position' => 2]);
    $partners = Wrestler::factory()->count(2)->create();
    $opponent = Wrestler::factory()->create();

    MatchCompetitor::factory()->for($match, 'eventMatch')->for($secondSide, 'side')->for($opponent, 'competitor')->create();

    foreach ($partners as $partner) {
        MatchCompetitor::factory()->for($match, 'eventMatch')->for($firstSide, 'side')->for($partner, 'competitor')->create();
    }

    // Act
    $query = $match->competitors();
    $query->with(['side', 'competitor']);
    $competitors = $query->get();
    $competitorsBySide = $competitors->competitorModelsBySidePosition();

    // Assert
    expect($competitorsBySide->keys()->all())->toBe([1, 2])
        ->and($competitorsBySide->get(1)?->pluck('id')->all())->toBe($partners->pluck('id')->all())
        ->and($competitorsBySide->get(2)?->pluck('id')->all())->toBe([$opponent->id]);
});

it('partitions competitor models by roster type', function () {
    // Arrange
    $match = EventMatch::factory()->create();
    $side = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create(['id' => $wrestler->id]);

    foreach ([$wrestler, $tagTeam] as $competitor) {
        MatchCompetitor::factory()->for($match, 'eventMatch')->for($side, 'side')->for($competitor, 'competitor')->create();
    }

    // Act
    $query = $match->competitors();
    $query->with('competitor');
    $competitors = $query->get();
    $wrestlers = $competitors->wrestlers();
    $tagTeams = $competitors->tagTeams();

    // Assert
    expect($wrestlers->pluck('id')->all())->toBe([$wrestler->id])
        ->and($wrestlers->first())->toBeInstanceOf(Wrestler::class)
        ->and($wrestlers->keys()->all())->toBe([0])
        ->and($tagTeams->pluck('id')->all())->toBe([$tagTeam->id])
        ->and($tagTeams->first())->toBeInstanceOf(TagTeam::class)
        ->and($tagTeams->keys()->all())->toBe([0]);
});
