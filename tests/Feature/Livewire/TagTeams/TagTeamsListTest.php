<?php

declare(strict_types=1);

use App\Livewire\TagTeams\TagTeamsList;
use Livewire\Livewire;

test('it should return correct view', function () {
    Livewire::test(TagTeamsList::class)
        ->assertViewIs('livewire.tag-teams.tag-teams-list');
});

test('it should pass correct data', function () {
    Livewire::test(TagTeamsList::class)
        ->assertViewHas('tagTeams');
});
