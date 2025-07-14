<?php

declare(strict_types=1);

use App\Http\Controllers\MatchesController;
use App\Models\Events\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for MatchesController.
 *
 * @see MatchesController
 */
describe('MatchesController', function () {
    describe('index', function () {
        /**
         * @see MatchesController::index()
         */
        test('index returns a view for administrator', function () {
            $event = Event::factory()->create();

            actingAs(administrator())
                ->get(action([MatchesController::class, 'index'], $event))
                ->assertOk()
                ->assertViewIs('matches.index')
                ->assertViewHas('event', $event);
        });

        /**
         * @see MatchesController::index()
         */
        test('basic user cannot view event matches', function () {
            $event = Event::factory()->create();

            actingAs(basicUser())
                ->get(action([MatchesController::class, 'index'], $event))
                ->assertForbidden();
        });

        /**
         * @see MatchesController::index()
         */
        test('guest cannot view event matches', function () {
            $event = Event::factory()->create();

            get(action([MatchesController::class, 'index'], $event))
                ->assertRedirect('/login');
        });
    });
});
