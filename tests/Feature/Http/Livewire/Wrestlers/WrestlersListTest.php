<?php

use App\Http\Livewire\Wrestlers\WrestlersList;
use Livewire\Livewire;

test('it should return correct view', function () {
    Livewire::test(WrestlersList::class)
        ->assertViewIs('livewire.wrestlers.wrestlers-list');
});

test('it should pass correct data', function () {
    Livewire::test(WrestlersList::class)
        ->assertViewHas('wrestlers');
});
