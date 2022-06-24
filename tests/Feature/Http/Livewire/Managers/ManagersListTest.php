<?php

use App\Http\Livewire\Managers\ManagersList;
use Livewire\Livewire;

test('it should return correct view', function () {
    Livewire::test(ManagersList::class)
        ->assertViewIs('livewire.managers.managers-list');
});

test('it should pass correct data', function () {
    Livewire::test(ManagersList::class)
        ->assertViewHas('managers');
});
