<?php

use App\Http\Livewire\EventMatches\MatchForm;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\MatchType;
use App\Models\Referee;
use App\Models\Title;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->event = Event::factory()->create();
});

test('it loads the correct view', function () {
    livewire(MatchForm::class, ['event' => $this->event, 'match' => new EventMatch()])
        ->assertSet('event', $this->event)
        ->assertSet('match', new EventMatch())
        ->assertViewIs('livewire.matches.create');
});

test('it passes the correct data to the view', function () {
    $matchTypes = MatchType::pluck('name', 'id');
    $referees = Referee::query()->get()->pluck('full_name', 'id');
    $titles = Title::pluck('name', 'id');

    livewire(MatchForm::class, ['event' => $this->event, 'match' => $match = new EventMatch()])
        ->assertViewHas('match', $match)
        ->assertViewHas('matchTypes', $matchTypes->escapeWhenCastingToString())
        ->assertViewHas('referees', $referees->escapeWhenCastingToString())
        ->assertViewHas('titles', $titles->escapeWhenCastingToString());
});

test('it updates the competitors view when the match type is changed', function () {
    $matchTypes = MatchType::pluck('name', 'id');

    $matchTypeChosen = $matchTypes->random();
    $matchType = MatchType::where('name', $matchTypeChosen)->first();

    livewire(MatchForm::class, ['event' => $this->event, 'match' => new EventMatch()])
        ->set('matchTypeId', $matchType->id)
        ->assertSet('subViewToUse', 'matches.types.'.$matchType->slug);
});
