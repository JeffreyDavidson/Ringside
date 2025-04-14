<?php

declare(strict_types=1);

use App\Http\Controllers\UsersController;
use App\Livewire\Users\Tables\UsersTable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('index returns a view', function () {
    actingAs(administrator())
        ->get(action([UsersController::class, 'index']))
        ->assertOk()
        ->assertViewIs('users.index')
        ->assertSeeLivewire(UsersTable::class);
});

test('a basic user cannot view Users index page', function () {
    actingAs(basicUser())
        ->get(action([UsersController::class, 'index']))
        ->assertForbidden()
        ->assertDontSeeLivewire(UsersTable::class);
});

test('a guest cannot view users index page', function () {
    get(action([UsersController::class, 'index']))
        ->assertRedirect(route('login'));
});
