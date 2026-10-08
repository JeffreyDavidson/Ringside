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
    /**
     * @see EventsController::show()
     */
    test('show returns a view', function () {
        $event = Event::factory()->create();

        actingAs(administrator())
            ->get(route('events.show', $event))
            ->assertViewIs('events.show')
            ->assertViewHas('event', $event)
            ->assertSeeHtml('aria-label="Content workspace"')
            ->assertSee($event->name)
            ->assertSee('Status')
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(MatchesTable::class);
    });

    /**
     * @see EventsController::show()
     */
    test('show loads only the relationship rendered by the event summary', function () {
        $event = Event::factory()->create();

        EventMatch::factory()->for($event)->create();

        actingAs(administrator())
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertViewHas('event', fn (Event $event): bool => $event->relationLoaded('venue')
                && ! $event->relationLoaded('matches'));
    });

    /**
     * @see EventsController::show()
     */
    test('a basic user cannot view an event profile', function () {
        $event = Event::factory()->create();

        actingAs(basicUser())
            ->get(route('events.show', $event))
            ->assertForbidden();
    });

    /**
     * @see EventsController::show()
     */
    test('a guest cannot view an event profile', function () {
        $event = Event::factory()->create();

        get(route('events.show', $event))
            ->assertRedirect(route('login'));
    });

    /**
     * @see EventsController::show()
     */
    test('returns 404 when event does not exist', function () {
        Event::factory()->create();

        actingAs(administrator())
            ->get(route('events.show', 999999))
            ->assertNotFound();
    });
});
