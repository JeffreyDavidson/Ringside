<?php

declare(strict_types=1);

use App\Http\Controllers\StablesController;
use App\Livewire\Stables\Tables\StablesTable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('index returns a view', function () {
    actingAs(administrator())
        ->get(action([StablesController::class, 'index']))
        ->assertOk()
        ->assertViewIs('stables.index')
        ->assertSeeLivewire(StablesTable::class);
});

test('a basic user cannot view stables index page', function () {
    actingAs(basicUser())
        ->get(action([StablesController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view stables index page', function () {
    get(action([StablesController::class, 'index']))
        ->assertRedirect(route('login'));
});
