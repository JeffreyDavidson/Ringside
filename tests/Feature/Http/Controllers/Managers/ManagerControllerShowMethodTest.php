<?php

use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->manager = Manager::factory()->create();
});

test('show returns a view', function () {
    actingAs(administrator())
        ->get(action([ManagersController::class, 'show'], $this->manager))
        ->assertViewIs('managers.show')
        ->assertViewHas('manager', $this->manager);
});

test('a basic user can view their manager profile', function () {
    $manager = Manager::factory()->for($user = basicUser())->create();

    actingAs($user)
        ->get(action([ManagersController::class, 'show'], $manager))
        ->assertOk();
});

test('a basic user cannot view another users manager profile', function () {
    $manager = Manager::factory()->for(User::factory())->create();

    actingAs(basicUser())
        ->get(action([ManagersController::class, 'show'], $manager))
        ->assertForbidden();
});

test('a guest cannot view a manager profile', function () {
    get(action([ManagersController::class, 'show'], $this->manager))
        ->assertRedirect(route('login'));
});
