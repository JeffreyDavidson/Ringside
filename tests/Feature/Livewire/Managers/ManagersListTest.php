<?php

declare(strict_types=1);

use App\Livewire\Managers\ManagersList;
use Livewire\Livewire;

test('it should return correct view', function () {
    Livewire::test(ManagersList::class)
        ->assertViewIs('livewire.managers.managers-list');
});

test('it should pass correct data', function () {
    Livewire::test(ManagersList::class)
        ->assertViewHas('managers');
});
