<?php

use App\Http\Controllers\Wrestlers\WrestlersController;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('index returns a view', function () {
    actingAs(administrator())
        ->get(action([WrestlersController::class, 'index']))
        ->assertOk()
        ->assertViewIs('wrestlers.index');
});

test('a basic user cannot view wrestlers index page', function () {
    actingAs(basicUser())
        ->get(action([WrestlersController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view wrestlers index page', function () {
    get(action([WrestlersController::class, 'index']))
        ->assertRedirect(route('login'));
});
