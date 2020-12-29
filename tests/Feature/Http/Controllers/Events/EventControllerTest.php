<?php

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\Role;
use App\Http\Controllers\Events\EventsController;
use App\Http\Requests\Events\StoreRequest;
use App\Http\Requests\Events\UpdateRequest;
use App\Models\Event;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group events
 * @group feature-events
 */
class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid parameters for request.
     *
     * @param  array  $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'name' => 'Example Event Name',
            'date' => '2020-10-21 19:00:00',
            'venue_id' => Venue::factory()->create()->id,
            'preview' => 'This is an event preview.',
        ], $overrides);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function index_returns_a_view($administrators)
    {
        $this->actAs($administrators)
            ->get(route('events.index'))
            ->assertOk()
            ->assertViewIs('events.index')
            ->assertSeeLivewire('events.scheduled-events')
            ->assertSeeLivewire('events.unscheduled-events')
            ->assertSeeLivewire('events.past-events');
    }

    /** @test */
    public function a_basic_user_cannot_view_events_index_page()
    {
        $this->actAs(Role::BASIC)
            ->get(route('events.index'))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_events_index_page()
    {
        $this->get(route('events.index'))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function create_returns_a_view($administrators)
    {
        $this->actAs($administrators)
            ->get(route('events.create'))
            ->assertViewIs('events.create')
            ->assertViewHas('event', new Event);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_an_event()
    {
        $this->actAs(Role::BASIC)
            ->get(route('events.create'))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_an_event()
    {
        $this->get(route('events.create'))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function store_creates_an_event_and_redirects($administrators)
    {
        $this->actAs($administrators)
            ->from(route('events.create'))
            ->post(route('events.store'), $this->validParams())
            ->assertRedirect(route('events.index'));

        tap(Event::first(), function ($event) {
            $this->assertEquals('Example Event Name', $event->name);
            $this->assertEquals('2020-10-21 19:00:00', $event->date);
            $this->assertEquals(1, $event->venue_id);
            $this->assertEquals('This is an event preview.', $event->preview);
        });
    }

    /** @test */
    public function a_basic_user_cannot_create_an_event()
    {
        $this->actAs(Role::BASIC)
            ->from(route('events.create'))
            ->post(route('events.store'), $this->validParams())
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_create_a_event()
    {
        $this->from(route('events.create'))
            ->post(route('events.store'), $this->validParams())
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(EventsController::class, 'store', StoreRequest::class);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function show_returns_a_view($administrators)
    {
        $event = Event::factory()->create();

        $this->actAs($administrators)
            ->get(route('events.show', $event))
            ->assertViewIs('events.show')
            ->assertViewHas('event', $event);
    }

    /** @test */
    public function a_basic_user_cannot_view_an_event_page()
    {
        $event = Event::factory()->create();

        $this->actAs(Role::BASIC)
            ->get(route('events.show', $event))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_an_event_page()
    {
        $event = Event::factory()->create();

        $this->get(route('events.show', $event))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_editing_a_scheduled_event()
    {
        $event = Event::factory()->scheduled()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->get(route('events.edit', $event))
            ->assertViewIs('events.edit')
            ->assertViewHas('event', $event);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_an_event()
    {
        $event = Event::factory()->create();

        $this->actAs(Role::BASIC)
            ->get(route('events.edit', $event))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_an_event()
    {
        $event = Event::factory()->create();

        $this->get(route('events.edit', $event))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function a_past_event_cannot_be_edited()
    {
        $event = Event::factory()->past()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->get(route('events.edit', $event))
            ->assertForbidden();
    }

    /** @test */
    public function an_administrator_can_update_a_scheduled_event()
    {
        $now = now();
        Carbon::setTestNow($now);

        $event = Event::factory()->scheduled()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->from(route('events.edit', $event))
            ->put(route('events.update', $event), $this->validParams())
            ->assertRedirect(route('events.index'));

        tap($event->fresh(), function ($event) use ($now) {
            $this->assertEquals('Example Event Name', $event->name);
            $this->assertEquals('2020-10-21 19:00:00', $event->date);
            $this->assertEquals('This is an event preview.', $event->preview);
        });
    }

    /** @test */
    public function a_basic_user_cannot_update_an_event()
    {
        $event = Event::factory()->create();

        $this->actAs(Role::BASIC)
            ->from(route('events.edit', $event))
            ->put(route('events.update', $event), $this->validParams())
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_update_an_event()
    {
        $event = Event::factory()->create();

        $this->from(route('events.edit', $event))
            ->put(route('events.update', $event), $this->validParams())
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function a_past_event_cannot_be_updated()
    {
        $event = Event::factory()->past()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->from(route('events.edit', $event))
            ->put(route('events.update', $event), $this->validParams())
            ->assertForbidden();
    }

    /** @test */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(EventsController::class, 'update', UpdateRequest::class);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deletes_an_event_and_redirects($administrators)
    {
        $event = Event::factory()->create();

        $this->actAs($administrators)
            ->delete(route('events.destroy', $event));

        $this->assertSoftDeleted($event);
    }

    /** @test */
    public function a_basic_user_cannot_delete_an_event()
    {
        $event = Event::factory()->create();

        $this->actAs(Role::BASIC)
            ->delete(route('events.destroy', $event))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_an_event()
    {
        $event = Event::factory()->create();

        $this->delete(route('events.destroy', $event))
            ->assertRedirect(route('login'));
    }
}
