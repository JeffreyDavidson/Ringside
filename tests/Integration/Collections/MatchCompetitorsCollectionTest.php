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
    $sides = MatchSide::factory()->for($match, 'match')->count(2)
        ->sequence(['position' => 1], ['position' => 2])
        ->create();
    $firstSide = $sides->firstOrFail();
    $secondSide = $sides->sole('position', 2);
    $partners = Wrestler::factory()->count(2)->create();
    $opponent = Wrestler::factory()->create();

    MatchCompetitor::factory()->for($match, 'eventMatch')->for($secondSide, 'side')->for($opponent, 'competitor')->create();

    MatchCompetitor::factory()->for($match, 'eventMatch')->for($firstSide, 'side')
        ->count($partners->count())
        ->sequence(...$partners->map(fn (Wrestler $partner): array => ['competitor_id' => $partner->id])->all())
        ->create();

    // Act
    $query = $match->competitors();
    $query->with(['side', 'competitor']);
    $competitors = $query->get();
    $competitorsBySide = $competitors->competitorModelsBySidePosition();

    // Assert
    expect($competitorsBySide->keys()->all())->toBe([1, 2])
        ->and($competitorsBySide->get(1)?->pluck('id')->all())->toEqualCanonicalizing($partners->pluck('id')->all())
        ->and($competitorsBySide->get(2)?->pluck('id')->all())->toBe([$opponent->id]);
});

it('partitions competitor models by roster type', function () {
    // Arrange
    $match = EventMatch::factory()->create();
    $side = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create(['id' => $wrestler->id]);

    MatchCompetitor::factory()->for($match, 'eventMatch')->for($side, 'side')->for($wrestler, 'competitor')->create();
    MatchCompetitor::factory()->for($match, 'eventMatch')->for($side, 'side')->for($tagTeam, 'competitor')->create();

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

it('groups competitor entries by ordered side position', function () {
    // Arrange
    $match = EventMatch::factory()->create();
    $sides = MatchSide::factory()->for($match, 'match')->count(2)
        ->sequence(['position' => 1], ['position' => 2])
        ->create();
    $firstSide = $sides->firstOrFail();
    $secondSide = $sides->sole('position', 2);
    $opponent = Wrestler::factory()->create();
    $partner = Wrestler::factory()->create();

    $opponentEntry = MatchCompetitor::factory()->for($match, 'eventMatch')->for($secondSide, 'side')->for($opponent, 'competitor')->create();
    $partnerEntry = MatchCompetitor::factory()->for($match, 'eventMatch')->for($firstSide, 'side')->for($partner, 'competitor')->create();

    // Act
    $query = $match->competitors();
    $query->with('side');
    $competitors = $query->get();
    $entriesBySide = $competitors->bySidePosition();

    // Assert
    expect($entriesBySide->keys()->all())->toBe([1, 2])
        ->and($entriesBySide->get(1)?->pluck('id')->all())->toBe([$partnerEntry->id])
        ->and($entriesBySide->get(2)?->pluck('id')->all())->toBe([$opponentEntry->id]);
});

it('returns the competitor models in entry order', function () {
    // Arrange
    $match = EventMatch::factory()->create();
    $side = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();

    MatchCompetitor::factory()->for($match, 'eventMatch')->for($side, 'side')->for($wrestler, 'competitor')->create();
    MatchCompetitor::factory()->for($match, 'eventMatch')->for($side, 'side')->for($tagTeam, 'competitor')->create();

    // Act
    $query = $match->competitors();
    $query->with('competitor');
    $competitors = $query->get();
    $models = $competitors->competitorModels();

    // Assert
    expect($models->keys()->all())->toBe([0, 1])
        ->and($models->first())->toBeInstanceOf(Wrestler::class)
        ->and($models->last())->toBeInstanceOf(TagTeam::class);
});
