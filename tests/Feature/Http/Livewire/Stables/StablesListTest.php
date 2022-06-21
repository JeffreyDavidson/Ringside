<?php

use App\Http\Livewire\Stables\StablesList;
use Livewire\Livewire;

test('it should return correct view', function () {
    Livewire::test(StablesList::class)
        ->assertViewIs('livewire.stables.stables-list');
});

test('it should pass correct data', function () {
    Livewire::test(StablesList::class)
        ->assertViewHas('stables');
});
