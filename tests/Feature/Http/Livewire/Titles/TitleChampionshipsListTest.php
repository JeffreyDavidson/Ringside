<?php

use App\Http\Livewire\Titles\TitleChampionshipsList;
use Livewire\Livewire;

test('it should return correct view', function () {
    Livewire::test(TitleChampionshipsList::class)
        ->assertViewIs('livewire.titles.title-championships-list');
});

test('it should pass correct data', function () {
    Livewire::test(TitleChampionshipsList::class)
        ->assertViewHas('titleChampionships');
});
