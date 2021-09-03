<?php

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\Role;
use App\Http\Controllers\Events\EventsController;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group events
 * @group feature-events
 */
class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    public function setUp(): void
    {
        parent::setUp();

        $this->event = Event::factory()->create();
    }

    /**
     * @test
     */
    public function index_returns_a_view()
    {
        $this
            ->actAs(Role::ADMINISTRATOR)
            ->get(action([EventsController::class, 'index']))
            ->assertOk()
            ->assertViewIs('events.index')
            ->assertSeeLivewire('events.scheduled-events')
            ->assertSeeLivewire('events.unscheduled-events')
            ->assertSeeLivewire('events.past-events');
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_events_index_page()
    {
        $this
            ->actAs(Role::BASIC)
            ->get(action([EventsController::class, 'index']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_events_index_page()
    {
        $this
            ->get(action([EventsController::class, 'index']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function show_returns_a_view()
    {
        $this
            ->actAs(Role::ADMINISTRATOR)
            ->get(action([EventsController::class, 'show'], $this->event))
            ->assertViewIs('events.show')
            ->assertViewHas('event', $this->event)
            ->assertViewMissing('event.venue');
    }

    /**
     * @test
     */
    public function show_returns_a_view_with_an_event_venue()
    {
        $venue = Venue::factory()->create();
        $event = Event::factory()->create(['venue_id' => $venue->id]);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->get(action([EventsController::class, 'show'], $event))
            ->assertViewIs('events.show')
            ->assertViewHas('event', $event)
            ->assertViewHas('event.venue');
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_an_event_page()
    {
        $this
            ->actAs(Role::BASIC)
            ->get(action([EventsController::class, 'show'], $this->event))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_an_event_page()
    {
        $this
            ->get(action([EventsController::class, 'show'], $this->event))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deletes_an_event_and_redirects()
    {
        $this
            ->actAs(Role::ADMINISTRATOR)
            ->delete(action([EventsController::class, 'destroy'], $this->event));

        $this->assertSoftDeleted($this->event);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_delete_an_event()
    {
        $this
            ->actAs(Role::BASIC)
            ->delete(action([EventsController::class, 'destroy'], $this->event))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_delete_an_event()
    {
        $this
            ->delete(action([EventsController::class, 'destroy'], $this->event))
            ->assertRedirect(route('login'));
    }
}
