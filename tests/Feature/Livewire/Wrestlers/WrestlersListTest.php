<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\WrestlersList;

use function Pest\Livewire\livewire;

test('it should return correct view', function () {
    livewire(WrestlersList::class)
        ->assertViewIs('livewire.wrestlers.wrestlers-list');
});

test('it should pass correct data', function () {
    livewire(WrestlersList::class)
        ->assertViewHas('wrestlers');
});
