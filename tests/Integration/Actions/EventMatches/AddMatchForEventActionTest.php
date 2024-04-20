<?php

declare(strict_types=1);

use App\Actions\EventMatches\AddCompetitorsToMatchAction;
use App\Actions\EventMatches\AddMatchForEventAction;
use App\Actions\EventMatches\AddRefereesToMatchAction;
use App\Actions\EventMatches\AddTitlesToMatchAction;
use App\Data\EventMatchData;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\MatchType;
use App\Models\Referee;
use App\Models\Title;
use App\Models\Wrestler;
use App\Repositories\EventMatchRepository;
use Database\Seeders\MatchTypesTableSeeder;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->event = Event::factory()->scheduled()->create();
    $this->eventMatchRepository = $this->mock(EventMatchRepository::class);
});

test('add a match to an event', function () {
    $matchType = MatchType::inRandomOrder()->first();
    $referees = Referee::factory()->count(1)->create();
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->count(2)->create();
    $competitors = collect([
        0 => [
            'wrestlers' => collect([$wrestlerA]),
        ],
        1 => [
            'wrestlers' => collect([$wrestlerB]),
        ],
    ]);
    $data = new EventMatchData($matchType, $referees, new Collection(), $competitors, null);

    $this->eventMatchRepository
        ->shouldReceive('createForEvent')
        ->with($this->event, $data)
        ->andReturn($eventMatch = new EventMatch);

    AddRefereesToMatchAction::shouldRun($this->event, $data->referees);
    AddTitlesToMatchAction::shouldNotRun();
    AddCompetitorsToMatchAction::shouldRun($eventMatch, $data->competitors);

    AddMatchForEventAction::run($this->event, $data);
});

test('add a title match to an event', function () {
    $matchType = MatchType::inRandomOrder()->first();
    $referees = Referee::factory()->count(1)->create();
    $titles = Title::factory()->count(1)->create();
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->count(2)->create();
    $competitors = collect([
        0 => [
            'wrestlers' => collect([$wrestlerA]),
        ],
        1 => [
            'wrestlers' => collect([$wrestlerB]),
        ],
    ]);
    $data = new EventMatchData($matchType, $referees, $titles, $competitors, null);

    $this->eventMatchRepository
        ->shouldReceive('createForEvent')
        ->with($this->event, $data)
        ->once()
        ->andReturn($eventMatch = new EventMatch);

    AddRefereesToMatchAction::shouldRun($this->event, $data->referees);
    AddTitlesToMatchAction::shouldRun($this->event, $data->titles);
    AddCompetitorsToMatchAction::shouldRun($eventMatch, $data->competitors);

    AddMatchForEventAction::run($this->event, $data);
});
