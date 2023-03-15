<?php

use App\Http\Livewire\Referees\RefereesList;
use function Pest\Livewire\livewire;

test('it should return correct view', function () {
    livewire(RefereesList::class)
        ->assertViewIs('livewire.referees.referees-list');
});

test('it should pass correct data', function () {
    livewire(RefereesList::class)
        ->assertViewHas('referees');
});
