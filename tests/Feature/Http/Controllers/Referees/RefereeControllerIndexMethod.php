<?php

use App\Http\Controllers\Referees\RefereesController;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('index returns a view', function () {
    actingAs(administrator())
        ->get(action([RefereesController::class, 'index']))
        ->assertOk()
        ->assertViewIs('referees.index')
        ->assertSeeLivewire('referees.referees-list');
});

test('a basic user cannot view referees index page', function () {
    actingAs(basicUser())
        ->get(action([RefereesController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view referees index page', function () {
    get(action([RefereesController::class, 'index']))
        ->assertRedirect(route('login'));
});
