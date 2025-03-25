<?php

declare(strict_types=1);

use App\Http\Controllers\Managers\ManagersController;
use App\Livewire\Managers\Tables\ManagersTable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('index returns a view', function () {
    actingAs(administrator())
        ->get(action([ManagersController::class, 'index']))
        ->assertOk()
        ->assertViewIs('managers.index')
        ->assertSeeLivewire(ManagersTable::class);
});

test('a basic user cannot view managers index page', function () {
    actingAs(basicUser())
        ->get(action([ManagersController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view managers index page', function () {
    get(action([ManagersController::class, 'index']))
        ->assertRedirect(route('login'));
});
