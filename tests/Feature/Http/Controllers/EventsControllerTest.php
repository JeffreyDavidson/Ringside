<?php

declare(strict_types=1);

use App\Http\Controllers\EventsController;
use App\Livewire\Events\Tables\EventsTable;
use App\Livewire\Matches\Tables\EventMatchesTable;
use App\Models\Events\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for EventsController.
 *
 * @see EventsController
 */
describe('index', function () {
    /**
     * @see EventsController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([EventsController::class, 'index']))
            ->assertOk()
            ->assertViewIs('events.index')
            ->assertSeeLivewire(EventsTable::class);
    });

    /**
     * @see EventsController::index()
     */
    test('a basic user cannot view events index page', function () {
        actingAs(basicUser())
            ->get(action([EventsController::class, 'index']))
            ->assertForbidden();
    });

    /**
     * @see EventsController::index()
     */
    test('a guest cannot view events index page', function () {
        get(action([EventsController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
    beforeEach(function () {
        $this->event = Event::factory()->create();
    });

    /**
     * @see EventsController::show()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([EventsController::class, 'show'], $this->event))
            ->assertViewIs('events.show')
            ->assertViewHas('event', $this->event)
            ->assertSeeLivewire(EventMatchesTable::class);
    });

    /**
     * @see EventsController::show()
     */
    test('a basic user cannot view an event profile', function () {
        actingAs(basicUser())
            ->get(action([EventsController::class, 'show'], $this->event))
            ->assertForbidden();
    });

    /**
     * @see EventsController::show()
     */
    test('a guest cannot view an event profile', function () {
        get(action([EventsController::class, 'show'], $this->event))
            ->assertRedirect(route('login'));
    });

    /**
     * @see EventsController::show()
     */
    test('returns 404 when event does not exist', function () {
        actingAs(administrator())
            ->get(action([EventsController::class, 'show'], 999999))
            ->assertNotFound();
    });
});
