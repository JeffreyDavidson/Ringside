<?php

use App\Http\Controllers\Managers\ManagersController;

test('create returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([ManagersController::class, 'create']))
        ->assertStatus(200)
        ->assertViewIs('managers.create');
});

test('a basic user cannot view the form for creating a manager', function () {
    $this->actingAs(basicUser())
        ->get(action([ManagersController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a manager', function () {
    $this->get(action([ManagersController::class, 'create']))
        ->assertRedirect(route('login'));
});
