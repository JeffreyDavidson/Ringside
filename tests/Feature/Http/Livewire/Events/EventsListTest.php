<?php

use App\Http\Livewire\Events\EventsList;
use Livewire\Livewire;

test('it should return correct view', function () {
    Livewire::test(EventsList::class)
        ->assertViewIs('livewire.events.events-list');
});

test('it should pass correct data', function () {
    Livewire::test(EventsList::class)
        ->assertViewHas('events');
});
