<?php

use App\Http\Controllers\Stables\StablesController;

test('create returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([StablesController::class, 'create']))
        ->assertStatus(200)
        ->assertViewIs('stables.create');
});

test('a basic user cannot view the form for creating a stable', function () {
    $this->actingAs(basicUser())
        ->get(action([StablesController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a stable', function () {
    $this->get(action([StablesController::class, 'create']))
        ->assertRedirect(route('login'));
});
