<?php

use function Pest\Livewire\livewire;

use App\Http\Livewire\Wrestlers\WrestlersList;

test('it should return correct view', function () {
    livewire(WrestlersList::class)
        ->assertViewIs('livewire.wrestlers.wrestlers-list');
});


test('it should pass correct data', function () {
    livewire(WrestlersList::class)
        ->assertViewHas('wrestlers');
});
