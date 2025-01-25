<?php

declare(strict_types=1);

use App\Http\Controllers\Stables\StablesController;
use App\Livewire\Stables\Tables\StablesTable;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([StablesController::class, 'index']))
        ->assertOk()
        ->assertViewIs('stables.index')
        ->assertSeeLivewire(StablesTable::class);
});

test('a basic user cannot view stables index page', function () {
    $this->actingAs(basicUser())
        ->get(action([StablesController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view stables index page', function () {
    $this->get(action([StablesController::class, 'index']))
        ->assertRedirect(route('login'));
});
