<?php

declare(strict_types=1);

use App\Actions\Matches\UpdateMatchAction;
use App\Data\Matches\EventMatchData;
use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Referees\Referee;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;

test('it atomically replaces a match configuration', function () {
    $match = EventMatch::factory()->create([
        'match_type' => MatchType::Singles,
        'preview' => 'Original preview',
    ]);
    $originalReferee = Referee::factory()->bookable()->create();
    $originalTitle = Title::factory()->active()->create();
    $originalWrestler = Wrestler::factory()->bookable()->create();
    $originalSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    MatchCompetitor::factory()->for($match, 'eventMatch')->for($originalSide, 'side')->create([
        'competitor_id' => $originalWrestler->id,
        'competitor_type' => $originalWrestler->getMorphClass(),
    ]);
    $match->referees()->attach($originalReferee);
    $match->titles()->attach($originalTitle);

    $newReferee = Referee::factory()->bookable()->create();
    $newTitle = Title::factory()->active()->create();
    $firstWrestler = Wrestler::factory()->bookable()->create();
    $secondWrestler = Wrestler::factory()->bookable()->create();
    $data = new EventMatchData(
        MatchType::TagTeam,
        Referee::query()->whereKey($newReferee)->get(),
        Title::query()->whereKey($newTitle)->get(),
        collect([
            1 => ['wrestlers' => [$firstWrestler]],
            2 => ['wrestlers' => [$secondWrestler]],
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
            $firstWrestler->id,
            $secondWrestler->id,
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
