<?php

declare(strict_types=1);

use App\Actions\Matches\UpdateMatchAction;
use App\Data\Matches\EventMatchData;
use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Enums\Titles\TitleType;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

test('it atomically replaces a match configuration', function () {
    $match = EventMatch::factory()->create([
        'match_type' => MatchType::Singles,
        'preview' => 'Original preview',
    ]);
    $originalReferee = Referee::factory()->bookable()->create();
    $originalTitle = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $originalWrestler = Wrestler::factory()->bookable()->create();
    $originalSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    MatchCompetitor::factory()->for($match, 'eventMatch')->for($originalSide, 'side')->create([
        'competitor_id' => $originalWrestler->id,
        'competitor_type' => $originalWrestler->getMorphClass(),
    ]);
    $match->referees()->attach($originalReferee);
    $match->titles()->attach($originalTitle);

    $newReferee = Referee::factory()->bookable()->create();
    $newTitle = Title::factory()->active()->create(['type' => TitleType::TagTeam]);
    $firstTagTeam = TagTeam::factory()->bookable()->create();
    $secondTagTeam = TagTeam::factory()->bookable()->create();
    $data = new EventMatchData(
        MatchType::TagTeam,
        Referee::query()->whereKey($newReferee)->get(),
        Title::query()->whereKey($newTitle)->get(),
        collect([
            1 => ['tag_teams' => [$firstTagTeam]],
            2 => ['tag_teams' => [$secondTagTeam]],
        ]),
        'Updated preview',
    );

    $updatedMatch = resolve(UpdateMatchAction::class)->handle($match, $data);

    expect($updatedMatch->match_type)->toBe(MatchType::TagTeam)
        ->and($updatedMatch->preview)->toBe('Updated preview')
        ->and($updatedMatch->match_number)->toBe($match->match_number)
        ->and($updatedMatch->referees->modelKeys())->toBe([$newReferee->id])
        ->and($updatedMatch->titles->modelKeys())->toBe([$newTitle->id])
        ->and($updatedMatch->competitors()->pluck('competitor_id')->all())->toEqualCanonicalizing([
            $firstTagTeam->id,
            $secondTagTeam->id,
        ]);
});

test('it rolls back the original configuration when a replacement is unavailable', function () {
    $match = EventMatch::factory()->create(['preview' => 'Original preview']);
    $originalReferee = Referee::factory()->bookable()->create();
    $originalWrestler = Wrestler::factory()->bookable()->create();
    $originalSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    MatchCompetitor::factory()->for($match, 'eventMatch')->for($originalSide, 'side')->create([
        'competitor_id' => $originalWrestler->id,
        'competitor_type' => $originalWrestler->getMorphClass(),
    ]);
    $match->referees()->attach($originalReferee);

    $newReferee = Referee::factory()->bookable()->create();
    $availableWrestler = Wrestler::factory()->bookable()->create();
    $unavailableWrestler = Wrestler::factory()->retired()->create();
    $data = new EventMatchData(
        MatchType::Singles,
        Referee::query()->whereKey($newReferee)->get(),
        Title::query()->whereKey([])->get(),
        collect([
            1 => ['wrestlers' => [$availableWrestler]],
            2 => ['wrestlers' => [$unavailableWrestler]],
        ]),
        'Updated preview',
    );

    expect(fn () => resolve(UpdateMatchAction::class)->handle($match, $data))
        ->toThrow(EntityNotAvailableException::class);

    $match->refresh();

    expect($match->preview)->toBe('Original preview')
        ->and($match->referees->modelKeys())->toBe([$originalReferee->id])
        ->and($match->competitors()->pluck('competitor_id')->all())->toBe([$originalWrestler->id]);
});

test('it rolls back the original configuration when the current champion is not a replacement competitor', function () {
    $match = EventMatch::factory()->create(['preview' => 'Original preview']);
    $originalReferee = Referee::factory()->bookable()->create();
    $originalWrestler = Wrestler::factory()->bookable()->create();
    $originalTitle = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $originalSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    MatchCompetitor::factory()->for($match, 'eventMatch')->for($originalSide, 'side')->create([
        'competitor_id' => $originalWrestler->id,
        'competitor_type' => $originalWrestler->getMorphClass(),
    ]);
    $match->referees()->attach($originalReferee);
    $match->titles()->attach($originalTitle);

    $newReferee = Referee::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $champion = Wrestler::factory()->bookable()->create();
    $firstChallenger = Wrestler::factory()->bookable()->create();
    $secondChallenger = Wrestler::factory()->bookable()->create();
    TitleChampionship::factory()->for($title)->forWrestler($champion)->current()->create();
    $data = new EventMatchData(
        MatchType::Singles,
        Referee::query()->whereKey($newReferee)->get(),
        Title::query()->whereKey($title)->get(),
        collect([
            1 => ['wrestlers' => [$firstChallenger]],
            2 => ['wrestlers' => [$secondChallenger]],
        ]),
        'Updated preview',
    );

    expect(fn () => resolve(UpdateMatchAction::class)->handle($match, $data))
        ->toThrow(InvalidMatchConfigurationException::class);

    $match->refresh();

    expect($match->preview)->toBe('Original preview')
        ->and($match->referees->modelKeys())->toBe([$originalReferee->id])
        ->and($match->titles->modelKeys())->toBe([$originalTitle->id])
        ->and($match->competitors()->pluck('competitor_id')->all())->toBe([$originalWrestler->id]);
});

test('it rejects reconfiguring a match after its result is recorded', function () {
    $match = EventMatch::factory()->create(['match_finish' => MatchFinish::TimeLimitDraw]);
    $referee = Referee::factory()->bookable()->create();
    $firstWrestler = Wrestler::factory()->bookable()->create();
    $secondWrestler = Wrestler::factory()->bookable()->create();
    $data = new EventMatchData(
        MatchType::Singles,
        Referee::query()->whereKey($referee)->get(),
        Title::query()->whereKey([])->get(),
        collect([
            1 => ['wrestlers' => [$firstWrestler]],
            2 => ['wrestlers' => [$secondWrestler]],
        ]),
        null,
    );

    expect(fn () => resolve(UpdateMatchAction::class)->handle($match, $data))
        ->toThrow(
            InvalidMatchConfigurationException::class,
            'A match cannot be reconfigured after its result has been recorded.',
        );
});
