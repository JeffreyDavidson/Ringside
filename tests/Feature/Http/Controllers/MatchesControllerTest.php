<?php

declare(strict_types=1);

use App\Http\Controllers\Matches\IndexController;
use App\Models\Events\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Matches Controllers.
 *
 * @see IndexController
 */
describe('MatchesController', function () {
    describe('index', function () {
        /**
         * @see IndexController::__invoke()
         */
        test('index returns a view for administrator', function () {
            $event = Event::factory()->create();

            actingAs(administrator())
                ->get(action(IndexController::class, $event))
                ->assertOk()
                ->assertViewIs('matches.index')
                ->assertViewHas('event', $event);
        });

        /**
         * @see IndexController::__invoke()
         */
        test('basic user cannot view event matches', function () {
            $event = Event::factory()->create();

            actingAs(basicUser())
                ->get(action(IndexController::class, $event))
                ->assertForbidden();
        });

        /**
         * @see IndexController::__invoke()
         */
        test('guest cannot view event matches', function () {
            $event = Event::factory()->create();

            get(action(IndexController::class, $event))
                ->assertRedirect('/login');
        });
    });
});
