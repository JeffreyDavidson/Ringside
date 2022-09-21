<?php

use App\Http\Controllers\Titles\TitlesController;

test('create returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([TitlesController::class, 'create']))
        ->assertViewIs('titles.create');
});

test('a basic user cannot view the form for creating a title', function () {
    $this->actingAs(basicUser())
        ->get(action([TitlesController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a title', function () {
    $this->get(action([TitlesController::class, 'create']))
        ->assertRedirect(route('login'));
});
