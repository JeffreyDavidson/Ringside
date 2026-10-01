<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Http\Controllers\Matches\EventMatchesController;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Event Matches Controller.
 *
 * @see EventMatchesController
 */
describe('Event Matches Controller', function () {
    /**
     * @see EventMatchesController::index()
     */
    test('index returns a view for administrator', function () {
        $event = Event::factory()->create();

        actingAs(administrator())
            ->get(route('events.matches.index', $event))
            ->assertOk()
            ->assertViewIs('matches.index')
            ->assertSeeHtml('aria-label="Content workspace"')
            ->assertViewHas('event', $event);
    });

    /**
     * @see EventMatchesController::index()
     */
    test('basic user cannot view event matches', function () {
        $event = Event::factory()->create();

        actingAs(basicUser())
            ->get(route('events.matches.index', $event))
            ->assertForbidden();
    });

    /**
     * @see EventMatchesController::index()
     */
    test('guest cannot view event matches', function () {
        $event = Event::factory()->create();

        get(route('events.matches.index', $event))
            ->assertRedirect(route('login'));
    });

    /**
     * @see EventMatchesController::index()
     */
    test('promotion member can view matches of their promotion event', function () {
        $promotion = Promotion::factory()->create();
        $event = Event::factory()->for($promotion, 'promotion')->create();
        $member = basicUser();
        $promotion->users()->attach($member, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);

        actingAs($member)
            ->get(route('events.matches.index', $event))
            ->assertOk()
            ->assertViewIs('matches.index');
    });

    /**
     * @see EventMatchesController::index()
     */
    test('promotion member cannot view matches of another promotion event', function () {
        $promotion = Promotion::factory()->create();
        $otherEvent = Event::factory()->for(Promotion::factory(), 'promotion')->create();
        $member = basicUser();
        $promotion->users()->attach($member, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);

        actingAs($member)
            ->get(route('events.matches.index', $otherEvent))
            ->assertNotFound();
    });
});
