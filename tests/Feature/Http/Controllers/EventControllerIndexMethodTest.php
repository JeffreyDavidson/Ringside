<?php

declare(strict_types=1);

use App\Http\Controllers\EventsController;
use App\Livewire\Events\Tables\EventsTable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('index returns a view', function () {
    actingAs(administrator())
        ->get(action([EventsController::class, 'index']))
        ->assertOk()
        ->assertViewIs('events.index')
        ->assertSeeLivewire(EventsTable::class);
});

test('a basic user cannot view events index page', function () {
    actingAs(basicUser())
        ->get(action([EventsController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view events index page', function () {
    get(action([EventsController::class, 'index']))
        ->assertRedirect(route('login'));
});
