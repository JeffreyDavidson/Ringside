<?php

declare(strict_types=1);

use App\Livewire\Titles\TitleChampionshipsList;

use function Pest\Livewire\livewire;

test('it should return correct view', function () {
    livewire(TitleChampionshipsList::class)
        ->assertViewIs('livewire.titles.title-championships-list');
});

test('it should pass correct data', function () {
    livewire(TitleChampionshipsList::class)
        ->assertViewHas('titleChampionships');
});
