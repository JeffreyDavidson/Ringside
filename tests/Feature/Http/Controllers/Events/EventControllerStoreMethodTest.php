<?php

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\Role;
use App\Http\Controllers\Events\EventsController;
use App\Models\Event;
use App\Models\Venue;
use Carbon\Carbon;
use Tests\Factories\EventRequestDataFactory;
use Tests\TestCase;

/**
 * @group events
 * @group feature-events
 */
class EventControllerStoreMethodTest extends TestCase
{
    /**
     * @test
     */
    public function create_returns_a_view()
    {
        $this
            ->actAs(Role::administrator())
            ->get(action([EventsController::class, 'create']))
            ->assertViewIs('events.create')
            ->assertViewHas('event', new Event);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_creating_an_event()
    {
        $this
            ->actAs(Role::basic())
            ->get(action([EventsController::class, 'create']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_creating_an_event()
    {
        $this
            ->get(action([EventsController::class, 'create']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function store_creates_an_event_and_redirects()
    {
        $this
            ->actAs(Role::administrator())
            ->from(action([EventsController::class, 'create']))
            ->post(
                action([EventsController::class, 'store']),
                EventRequestDataFactory::new()->create([
                    'name' => 'Example Event Name',
                    'date' => null,
                    'venue_id' => null,
                    'preview' => null,
                ])
            )
            ->assertRedirect(action([EventsController::class, 'index']));

        tap(Event::first(), function ($event) {
            $this->assertEquals('Example Event Name', $event->name);
            $this->assertNull($event->date);
            $this->assertNull($event->venue_id);
            $this->assertNull($event->preview);
        });
    }

    /**
     * @test
     */
    public function store_creates_an_event_with_a_venue_and_redirects()
    {
        $venue = Venue::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([EventsController::class, 'create']))
            ->post(
                action([EventsController::class, 'store']),
                EventRequestDataFactory::new()->create([
                    'venue_id' => $venue->id,
                ])
            );

        tap(Event::first(), function ($event) use ($venue) {
            $this->assertTrue($event->venue->is($venue));
        });
    }

    /**
     * @test
     */
    public function store_creates_an_event_with_a_date_and_redirects()
    {
        $this
            ->actAs(Role::administrator())
            ->from(action([EventsController::class, 'create']))
            ->post(
                action([EventsController::class, 'store']),
                EventRequestDataFactory::new()->create([
                    'date' => Carbon::tomorrow()->toDateTimeString(),
                ])
            );

        tap(Event::first(), function ($event) {
            $this->assertTrue($event->date->eq(Carbon::tomorrow()));
        });
    }

    /**
     * @test
     */
    public function store_creates_an_event_with_a_preview_and_redirects()
    {
        $this
            ->actAs(Role::administrator())
            ->from(action([EventsController::class, 'create']))
            ->post(
                action([EventsController::class, 'store']),
                EventRequestDataFactory::new()->create([
                    'preview' => 'This is a general event preview.',
                ])
            );

        tap(Event::first(), function ($event) {
            $this->assertEquals('This is a general event preview.', $event->preview);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_create_an_event()
    {
        $this
            ->actAs(Role::basic())
            ->from(action([EventsController::class, 'create']))
            ->post(action([EventsController::class, 'store']), EventRequestDataFactory::new()->create())
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_create_a_event()
    {
        $this
            ->from(action([EventsController::class, 'create']))
            ->post(action([EventsController::class, 'store']), EventRequestDataFactory::new()->create())
            ->assertRedirect(route('login'));
    }
}
