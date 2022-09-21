<?php

use App\Http\Controllers\Referees\RefereesController;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([RefereesController::class, 'index']))
        ->assertOk()
        ->assertViewIs('referees.index')
        ->assertSeeLivewire('referees.referees-list');
});

test('a basic user cannot view referees index page', function () {
    $this->actingAs(basicUser())
        ->get(action([RefereesController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view referees index page', function () {
    $this->get(action([RefereesController::class, 'index']))
        ->assertRedirect(route('login'));
});
