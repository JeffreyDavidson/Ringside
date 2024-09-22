<?php

declare(strict_types=1);

use App\Livewire\Titles\TitlesList;

use function Pest\Livewire\livewire;

test('it should return correct view', function () {
    livewire(TitlesList::class)
        ->assertViewIs('livewire.titles.titles-list');
});

test('it should pass correct data', function () {
    livewire(TitlesList::class)
        ->assertViewHas('titles');
});
