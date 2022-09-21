<?php

use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;
use App\Models\User;

beforeEach(function () {
    $this->manager = Manager::factory()->create();
});

test('show returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([ManagersController::class, 'show'], $this->manager))
        ->assertViewIs('managers.show')
        ->assertViewHas('manager', $this->manager);
});

test('a basic user can view their manager profile', function () {
    $manager = Manager::factory()->for($user = basicUser())->create();

    $this->actingAs($user)
        ->get(action([ManagersController::class, 'show'], $manager))
        ->assertOk();
});

test('a basic user cannot view another users manager profile', function () {
    $manager = Manager::factory()->for(User::factory())->create();

    $this->actingAs(basicUser())
        ->get(action([ManagersController::class, 'show'], $manager))
        ->assertForbidden();
});

test('a guest cannot view a manager profile', function () {
    $this->get(action([ManagersController::class, 'show'], $this->manager))
        ->assertRedirect(route('login'));
});
