<?php

use App\Http\Controllers\Titles\TitlesController;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([TitlesController::class, 'index']))
        ->assertOk()
        ->assertViewIs('titles.index');
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
