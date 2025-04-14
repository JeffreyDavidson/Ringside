<?php

declare(strict_types=1);

use App\Http\Controllers\EventsController;
use App\Livewire\EventMatches\Tables\EventMatchesTable;
use App\Livewire\Events\Tables\EventsTable;
use App\Models\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('index', function () {
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
});

describe('show', function () {
    beforeEach(function () {
        $this->event = Event::factory()->create();
    });

    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([EventsController::class, 'show'], $this->event))
            ->assertViewIs('events.show')
            ->assertViewHas('event', $this->event)
            ->assertSeeLivewire(EventMatchesTable::class);
    });

    test('a basic user cannot view an event profile', function () {
        actingAs(basicUser())
            ->get(action([EventsController::class, 'show'], $this->event))
            ->assertForbidden();
    });

    test('a guest cannot view an event profile', function () {
        get(action([EventsController::class, 'show'], $this->event))
            ->assertRedirect(route('login'));
    });
});
