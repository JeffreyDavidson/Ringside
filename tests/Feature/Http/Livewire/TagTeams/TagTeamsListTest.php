<?php

use App\Http\Livewire\TagTeams\TagTeamsList;
use Livewire\Livewire;

test('it should return correct view', function () {
    Livewire::test(TagTeamsList::class)
        ->assertViewIs('livewire.tagteams.tagteams-list');
});

test('it should pass correct data', function () {
    Livewire::test(TagTeamsList::class)
        ->assertViewHas('tagTeams');
});
