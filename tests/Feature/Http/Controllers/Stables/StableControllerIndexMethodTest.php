<?php

use App\Http\Controllers\Stables\StablesController;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([StablesController::class, 'index']))
        ->assertOk()
        ->assertViewIs('stables.index')
        ->assertSeeLivewire('stables.stables-list');
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
