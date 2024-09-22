<?php

declare(strict_types=1);

use App\Livewire\EventMatches\MatchForm;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\MatchType;
use App\Models\Referee;
use App\Models\Title;
use Database\Seeders\MatchTypesTableSeeder;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->event = Event::factory()->create();
});

test('it loads the correct view', function () {
    livewire(MatchForm::class, ['event' => $this->event, 'match' => new EventMatch()])
        ->assertSet('event', $this->event)
        ->assertSet('match', new EventMatch())
        ->assertViewIs('livewire.event-matches.match-form');
});

test('it passes the the match to the view', function () {
    livewire(MatchForm::class, ['event' => $this->event, 'match' => $match = new EventMatch()])
        ->assertViewHas('match', $match);
});

test('it passes the match types to the view', function () {
    $matchTypes = MatchType::pluck('name', 'id');

    livewire(MatchForm::class, ['event' => $this->event, 'match' => new EventMatch()])
        ->assertViewHas('matchTypes', $matchTypes->escapeWhenCastingToString());
});

test('it passes the referees to the view', function () {
    $referees = Referee::query()->get()->pluck('full_name', 'id');

    livewire(MatchForm::class, ['event' => $this->event, 'match' => new EventMatch()])
        ->assertViewHas('referees', $referees->escapeWhenCastingToString());
});

test('it passes the titles to the view', function () {
    $titles = Title::pluck('name', 'id');

    livewire(MatchForm::class, ['event' => $this->event, 'match' => new EventMatch()])
        ->assertViewHas('titles', $titles->escapeWhenCastingToString());
});

test('it updates the competitors view when the match type is changed', function () {
    $matchTypes = MatchType::pluck('name', 'id');

    $matchTypeChosen = $matchTypes->random();
    $matchType = MatchType::where('name', $matchTypeChosen)->first();

    livewire(MatchForm::class, ['event' => $this->event, 'match' => new EventMatch()])
        ->set('matchTypeId', $matchType->id)
        ->assertSet('subViewToUse', 'event-matches.types.'.$matchType->slug);
});
