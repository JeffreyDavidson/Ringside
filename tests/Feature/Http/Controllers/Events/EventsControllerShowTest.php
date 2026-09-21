<?php

declare(strict_types=1);

use App\Http\Controllers\Events\EventsController;
use App\Livewire\Matches\Tables\MatchesTable;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Events Controller.
 *
 * @see EventsController
 */
describe('Events Controller', function () {
    beforeEach(function () {
        $this->event = Event::factory()->create();
    });

    /**
     * @see EventsController::show()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(route('events.show', $this->event))
            ->assertViewIs('events.show')
            ->assertViewHas('event', $this->event)
            ->assertSeeLivewire(MatchesTable::class);
    });

    /**
     * @see EventsController::show()
     */
    test('show loads only the relationship rendered by the event summary', function () {
        EventMatch::factory()->for($this->event)->create();

        actingAs(administrator())
            ->get(route('events.show', $this->event))
            ->assertOk()
            ->assertViewHas('event', fn (Event $event): bool => $event->relationLoaded('venue')
                && ! $event->relationLoaded('matches'));
    });

    /**
     * @see EventsController::show()
     */
    test('a basic user cannot view an event profile', function () {
        actingAs(basicUser())
            ->get(route('events.show', $this->event))
            ->assertForbidden();
    });

    /**
     * @see EventsController::show()
     */
    test('a guest cannot view an event profile', function () {
        get(route('events.show', $this->event))
            ->assertRedirect(route('login'));
    });

    /**
     * @see EventsController::show()
     */
    test('returns 404 when event does not exist', function () {
        actingAs(administrator())
            ->get(route('events.show', 999999))
            ->assertNotFound();
    });
});
