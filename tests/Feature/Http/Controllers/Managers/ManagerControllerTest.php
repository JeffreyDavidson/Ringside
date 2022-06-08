<?php

use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;
use App\Models\User;

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

test('show returns a view', function () {
    $manager = Manager::factory()->create();

    $this->actingAs(administrator())
        ->get(action([ManagersController::class, 'show'], $manager))
        ->assertViewIs('managers.show')
        ->assertViewHas('manager', $manager);
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
    $manager = Manager::factory()->create();

    $this->get(action([ManagersController::class, 'show'], $manager))
        ->assertRedirect(route('login'));
});

test('deletes a manager and redirects', function () {
    $manager = Manager::factory()->create();

    $this->actingAs(administrator())
        ->delete(action([ManagersController::class, 'destroy'], $manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    $this->assertSoftDeleted($manager);
});

test('a basic user cannot delete a manager', function () {
    $manager = Manager::factory()->create();

    $this->actingAs(basicUser())
        ->delete(action([ManagersController::class, 'destroy'], $manager))
        ->assertForbidden();
});

test('a guest cannot delete a manager', function () {
    $manager = Manager::factory()->create();

    $this->delete(action([ManagersController::class, 'destroy'], $manager))
        ->assertRedirect(route('login'));
});
