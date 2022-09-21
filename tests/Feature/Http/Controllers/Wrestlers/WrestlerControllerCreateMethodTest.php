<?php

use App\Http\Controllers\Wrestlers\WrestlersController;

test('create returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([WrestlersController::class, 'create']))
        ->assertStatus(200)
        ->assertViewIs('wrestlers.create');
});

test('a basic user cannot view the form for creating a wrestler', function () {
    $this->actingAs(basicUser())
        ->get(action([WrestlersController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a wrestler', function () {
    $this->get(action([WrestlersController::class, 'create']))
        ->assertRedirect(route('login'));
});
