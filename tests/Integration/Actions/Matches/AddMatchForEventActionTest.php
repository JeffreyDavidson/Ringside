<?php

declare(strict_types=1);

use App\Actions\Matches\AddMatchForEventAction;
use App\Data\Matches\EventMatchData;
use App\Enums\MatchType;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Models\Events\Event;
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
    $wrestlers = [
        Wrestler::factory()->bookable()->create(),
        Wrestler::factory()->bookable()->create(),
    ];
    $matchData = new EventMatchData(
        MatchType::Singles,
        Referee::query()->whereKey([])->get(),
        Title::query()->whereKey([])->get(),
        collect(['wrestlers' => $wrestlers]),
        null,
    );

    expect(fn () => resolve(AddMatchForEventAction::class)->handle($event, $matchData))
        ->toThrow(InvalidMatchConfigurationException::class, 'A match must have at least one referee assigned.');
});
