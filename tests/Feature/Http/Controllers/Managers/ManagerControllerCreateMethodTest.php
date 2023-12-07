<?php

declare(strict_types=1);

use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('create returns a view', function () {
    actingAs(administrator())
        ->get(action([ManagersController::class, 'create']))
        ->assertOk()
        ->assertViewIs('managers.create')
        ->assertViewHas('manager', new Manager);
});

test('a basic user cannot view the form for creating a manager', function () {
    actingAs(basicUser())
        ->get(action([ManagersController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a manager', function () {
    get(action([ManagersController::class, 'create']))
        ->assertRedirect(route('login'));
});
