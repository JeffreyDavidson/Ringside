<?php

declare(strict_types=1);

use App\Http\Controllers\EventMatchesController;
use App\Models\Events\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for EventMatchesController.
 *
 * @see EventMatchesController
 */
describe('EventMatchesController', function () {
    describe('index', function () {
        /**
         * @see EventMatchesController::index()
         */
        test('index returns a view for administrator', function () {
            $event = Event::factory()->create();

            actingAs(administrator())
                ->get(action([EventMatchesController::class, 'index'], $event))
                ->assertOk()
                ->assertViewIs('event-matches.index')
                ->assertViewHas('event', $event);
        });

        /**
         * @see EventMatchesController::index()
         */
        test('basic user cannot view event matches', function () {
            $event = Event::factory()->create();

            actingAs(basicUser())
                ->get(action([EventMatchesController::class, 'index'], $event))
                ->assertForbidden();
        });

        /**
         * @see EventMatchesController::index()
         */
        test('guest cannot view event matches', function () {
            $event = Event::factory()->create();

            get(action([EventMatchesController::class, 'index'], $event))
                ->assertRedirect('/login');
        });
    });
});
