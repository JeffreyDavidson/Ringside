<?php

use App\Http\Livewire\Titles\TitlesList;
use Livewire\Livewire;

test('it should return correct view', function () {
    Livewire::test(TitlesList::class)
        ->assertViewIs('livewire.titles.titles-list');
});

test('it should pass correct data', function () {
    Livewire::test(TitlesList::class)
        ->assertViewHas('titles');
});
