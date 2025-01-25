<?php

declare(strict_types=1);

use App\Http\Controllers\Titles\TitlesController;
use App\Livewire\Titles\Tables\TitlesTable;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([TitlesController::class, 'index']))
        ->assertOk()
        ->assertViewIs('titles.index')
        ->assertSeeLivewire(TitlesTable::class);
});

test('a basic user cannot view titles index page', function () {
    $this->actingAs(basicUser())
        ->get(action([TitlesController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view titles index page', function () {
    $this->get(action([TitlesController::class, 'index']))
        ->assertRedirect(route('login'));
});
