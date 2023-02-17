<?php

use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->manager = Manager::factory()->create();
});

test('edit returns a view', function () {
    actingAs(administrator())
        ->get(action([ManagersController::class, 'edit'], $this->manager))
        ->assertSuccessful()
        ->assertViewIs('managers.edit')
        ->assertViewHas('manager', $this->manager);
});

test('a basic user cannot view the form for editing a manager', function () {
    $this->actingAs(basicUser())
        ->get(action([ManagersController::class, 'edit'], $this->manager))
        ->assertForbidden();
});

test('a guest cannot view the form for editing a manager', function () {
    $this->get(action([ManagersController::class, 'edit'], $this->manager))
        ->assertRedirect(route('login'));
});
