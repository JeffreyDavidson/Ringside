<?php

declare(strict_types=1);

use App\Http\Controllers\Titles\TitlesController;
use App\Livewire\Titles\Tables\TitlesTable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('index returns a view', function () {
    actingAs(administrator())
        ->get(action([TitlesController::class, 'index']))
        ->assertOk()
        ->assertViewIs('titles.index')
        ->assertSeeLivewire(TitlesTable::class);
});

test('a basic user cannot view titles index page', function () {
    actingAs(basicUser())
        ->get(action([TitlesController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view titles index page', function () {
    get(action([TitlesController::class, 'index']))
        ->assertRedirect(route('login'));
});
