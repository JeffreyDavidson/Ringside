<?php

use App\Http\Controllers\Wrestlers\WrestlersController;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([WrestlersController::class, 'index']))
        ->assertOk()
        ->assertViewIs('wrestlers.index')
        ->assertSeeLivewire('wrestlers.wrestlers-list');
});

test('a basic user cannot view wrestlers index page', function () {
    $this->actingAs(basicUser())
        ->get(action([WrestlersController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view wrestlers index page', function () {
    $this->get(action([WrestlersController::class, 'index']))
        ->assertRedirect(route('login'));
});
