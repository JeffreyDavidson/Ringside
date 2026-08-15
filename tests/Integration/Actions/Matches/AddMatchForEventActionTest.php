<?php

declare(strict_types=1);

use App\Actions\Matches\AddMatchForEventAction;
use App\Data\Matches\EventMatchData;
use App\Enums\MatchType;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Referees\Referee;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;

test('it rejects a match without competitors', function () {
    $event = Event::factory()->create();
    $matchData = new EventMatchData(
        MatchType::Singles,
        Referee::factory()->bookable()->count(1)->create(),
        Title::query()->whereKey([])->get(),
        collect(),
        null,
    );

    expect(fn () => resolve(AddMatchForEventAction::class)->handle($event, $matchData))
        ->toThrow(InvalidMatchConfigurationException::class, 'A match must have competitors assigned.');
});

test('it rejects a match without referees', function () {
    $event = Event::factory()->create();
    $firstWrestler = Wrestler::factory()->bookable()->create();
    $secondWrestler = Wrestler::factory()->bookable()->create();
    $matchData = new EventMatchData(
        MatchType::Singles,
        Referee::query()->whereKey([])->get(),
        Title::query()->whereKey([])->get(),
        collect([
            1 => ['wrestlers' => [$firstWrestler]],
            2 => ['wrestlers' => [$secondWrestler]],
        ]),
        null,
    );

    expect(fn () => resolve(AddMatchForEventAction::class)->handle($event, $matchData))
        ->toThrow(InvalidMatchConfigurationException::class, 'A match must have at least one referee assigned.');
});

test('it creates a complete side-based match', function () {
    $event = Event::factory()->create();
    $referee = Referee::factory()->bookable()->create();
    $title = Title::factory()->active()->create();
    $firstWrestler = Wrestler::factory()->bookable()->create();
    $secondWrestler = Wrestler::factory()->bookable()->create();
    $matchData = new EventMatchData(
        MatchType::Singles,
        Referee::query()->whereKey($referee)->get(),
        Title::query()->whereKey($title)->get(),
        collect([
            1 => ['wrestlers' => [$firstWrestler]],
            2 => ['wrestlers' => [$secondWrestler]],
        ]),
        'Opening contest',
    );

    $match = resolve(AddMatchForEventAction::class)->handle($event, $matchData);

    expect($match->event->is($event))->toBeTrue()
        ->and($match->match_number)->toBe(1)
        ->and($match->match_type)->toBe(MatchType::Singles)
        ->and($match->preview)->toBe('Opening contest')
        ->and($match->referees->modelKeys())->toBe([$referee->id])
        ->and($match->titles->modelKeys())->toBe([$title->id])
        ->and($match->sides()->pluck('position')->all())->toBe([1, 2])
        ->and($match->competitors()->pluck('competitor_id')->all())->toEqualCanonicalizing([
            $firstWrestler->id,
            $secondWrestler->id,
        ]);
});

test('it does not reuse match numbers from soft-deleted matches', function () {
    $event = Event::factory()->create();
    EventMatch::factory()->for($event)->create(['match_number' => 4])->delete();
    $referee = Referee::factory()->bookable()->create();
    $firstWrestler = Wrestler::factory()->bookable()->create();
    $secondWrestler = Wrestler::factory()->bookable()->create();
    $matchData = new EventMatchData(
        MatchType::Singles,
        Referee::query()->whereKey($referee)->get(),
        Title::query()->whereKey([])->get(),
        collect([
            1 => ['wrestlers' => [$firstWrestler]],
            2 => ['wrestlers' => [$secondWrestler]],
        ]),
        null,
    );

    $match = resolve(AddMatchForEventAction::class)->handle($event, $matchData);

    expect($match->match_number)->toBe(5);
});

test('it rolls back the match when a side contains no eligible competitors', function () {
    $event = Event::factory()->create();
    $referee = Referee::factory()->bookable()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $matchData = new EventMatchData(
        MatchType::Singles,
        Referee::query()->whereKey($referee)->get(),
        Title::query()->whereKey([])->get(),
        collect([
            1 => ['wrestlers' => [$bookableWrestler]],
            2 => ['wrestlers' => [$unemployedWrestler]],
        ]),
        null,
    );

    expect(fn () => resolve(AddMatchForEventAction::class)->handle($event, $matchData))
        ->toThrow(EntityNotAvailableException::class);

    expect(EventMatch::query()->whereBelongsTo($event)->exists())->toBeFalse();
});
