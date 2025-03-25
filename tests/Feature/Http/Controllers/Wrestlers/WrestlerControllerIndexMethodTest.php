<?php

declare(strict_types=1);

use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Livewire\Wrestlers\Tables\WrestlersTable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('index returns a view', function () {
    actingAs(administrator())
        ->get(action([WrestlersController::class, 'index']))
        ->assertOk()
        ->assertViewIs('wrestlers.index')
        ->assertSeeLivewire(WrestlersTable::class);
});

test('a basic user cannot view wrestlers index page', function () {
    actingAs(basicUser())
        ->get(action([WrestlersController::class, 'index']))
        ->assertForbidden()
        ->assertDontSeeLivewire(WrestlersTable::class);
});

test('a guest cannot view wrestlers index page', function () {
    get(action([WrestlersController::class, 'index']))
        ->assertRedirect(route('login'));
});
