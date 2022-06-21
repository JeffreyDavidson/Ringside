<?php

use App\Http\Livewire\Referees\RefereesList;
use Livewire\Livewire;

test('it should return correct view', function () {
    Livewire::test(RefereesList::class)
        ->assertViewIs('livewire.referees.referees-list');
});

test('it should pass correct data', function () {
    Livewire::test(RefereesList::class)
        ->assertViewHas('referees');
});
