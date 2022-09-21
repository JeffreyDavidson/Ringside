<?php

use App\Http\Controllers\Managers\ManagersController;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([ManagersController::class, 'index']))
        ->assertOk()
        ->assertViewIs('managers.index')
        ->assertSeeLivewire('managers.managers-list');
});

test('a basic user cannot view managers index page', function () {
    $this->actingAs(basicUser())
        ->get(action([ManagersController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view managers index page', function () {
    $this->get(action([ManagersController::class, 'index']))
        ->assertRedirect(route('login'));
});
