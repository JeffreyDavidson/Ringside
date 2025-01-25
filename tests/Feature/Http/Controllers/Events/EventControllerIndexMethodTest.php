<?php

declare(strict_types=1);

use App\Http\Controllers\Events\EventsController;
use App\Livewire\Events\Tables\EventsTable;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([EventsController::class, 'index']))
        ->assertOk()
        ->assertViewIs('events.index')
        ->assertSeeLivewire(EventsTable::class);
});

test('a basic user cannot view events index page', function () {
    $this->actingAs(basicUser())
        ->get(action([EventsController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view events index page', function () {
    $this->get(action([EventsController::class, 'index']))
        ->assertRedirect(route('login'));
});
