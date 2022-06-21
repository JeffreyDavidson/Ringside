<?php

use App\Http\Livewire\Venues\VenuesList;
use Livewire\Livewire;

test('it should return correct view', function () {
    Livewire::test(VenuesList::class)
        ->assertViewIs('livewire.venues.venues-list');
});

test('it should pass correct data', function () {
    Livewire::test(VenuesList::class)
        ->assertViewHas('venues');
});
