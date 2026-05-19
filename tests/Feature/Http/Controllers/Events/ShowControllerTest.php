<?php

declare(strict_types=1);

use App\Http\Controllers\Events\ShowController;
use App\Livewire\Matches\Tables\MatchesTable;
use App\Models\Events\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Events Show Controller.
 *
 * @see ShowController
 */
describe('Events Show Controller', function () {
    beforeEach(function () {
        $this->event = Event::factory()->create();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, $this->event))
            ->assertViewIs('events.show')
            ->assertViewHas('event', $this->event)
            ->assertSeeLivewire(MatchesTable::class);
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a basic user cannot view an event profile', function () {
        actingAs(basicUser())
            ->get(action(ShowController::class, $this->event))
            ->assertForbidden();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a guest cannot view an event profile', function () {
        get(action(ShowController::class, $this->event))
            ->assertRedirect(route('login'));
    });

    /**
     * @see ShowController::__invoke()
     */
    test('returns 404 when event does not exist', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, 999999))
            ->assertNotFound();
    });
});
