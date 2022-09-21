<?php

use App\Http\Controllers\Referees\RefereesController;

test('create returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([RefereesController::class, 'create']))
        ->assertStatus(200)
        ->assertViewIs('referees.create');
});

test('a basic user cannot view the form for creating a referee', function () {
    $this->actingAs(basicUser())
        ->get(action([RefereesController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a referee', function () {
    $this->get(action([RefereesController::class, 'create']))
        ->assertRedirect(route('login'));
});
