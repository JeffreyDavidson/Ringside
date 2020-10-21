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
        $this->actAs($administrators);

        $response = $this->indexRequest('events');

        $response->assertOk();
        $response->assertViewIs('events.index');
        $response->assertSeeLivewire('events.scheduled-events');
        $response->assertSeeLivewire('events.unscheduled-events');
        $response->assertSeeLivewire('events.past-events');
    }

    /** @test */
    public function a_basic_user_cannot_view_events_index_page()
    {
        $this->actAs(Role::BASIC);

        $this->indexRequest('events')->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_events_index_page()
    {
        $this->indexRequest('events')->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function create_returns_a_view($administrators)
    {
        $this->actAs($administrators);

        $response = $this->createRequest('events');

        $response->assertViewIs('events.create');
        $response->assertViewHas('event', new Event);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_an_event()
    {
        $this->actAs(Role::BASIC);

        $this->createRequest('event')->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_an_event()
    {
        $response = $this->createRequest('event');

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function store_creates_an_event_and_redirects($administrators)
    {
        $this->actAs($administrators);

        $response = $this->storeRequest('event', $this->validParams());

        $response->assertRedirect(route('events.index'));
        tap(Event::first(), function ($event) {
            $this->assertEquals('Example Event Name', $event->name);
            $this->assertEquals('2020-10-21 19:00:00', $event->date);
            $this->assertEquals(1, $event->venue_id);
            $this->assertEquals('This is an event preview.', $event->preview);
        });
    }

    /** @test */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            EventsController::class,
            'store',
            StoreRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_create_an_evnet()
    {
        $this->actAs(Role::BASIC);

        $this->storeRequest('event', $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_create_a_event()
    {
        $this->storeRequest('event', $this->validParams())->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function show_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $event = Event::factory()->create();

        $response = $this->showRequest($event);

        $response->assertViewIs('events.show');
        $this->assertTrue($response->data('event')->is($event));
    }

    /** @test */
    public function a_basic_user_cannot_view_an_event_page()
    {
        $this->actAs(Role::BASIC);
        $event = Event::factory()->create();

        $response = $this->showRequest($event);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_an_event_page()
    {
        $event = Event::factory()->create();

        $response = $this->showRequest($event);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_editing_a_scheduled_event()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = Event::factory()->scheduled()->create();

        $response = $this->get(route('events.edit', $event));

        $response->assertViewIs('events.edit');
        $this->assertTrue($response->data('event')->is($event));
    }

    /** @test */
    public function an_administrator_can_update_a_scheduled_event()
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);
        $event = Event::factory()->scheduled()->create();

        $response = $this->patch(route('events.update', $event), $this->validParams());

        $response->assertRedirect(route('events.index'));
        tap($event->fresh(), function ($event) use ($now) {
            $this->assertEquals('Example Event Name', $event->name);
            $this->assertEquals('2020-10-21 19:00:00', $event->date);
            $this->assertEquals('This is an event preview.', $event->preview);
        });
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_an_event()
    {
        $this->actAs(Role::BASIC);
        $event = Event::factory()->create();

        $response = $this->editRequest($event);

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_an_event()
    {
        $this->actAs(Role::BASIC);
        $event = Event::factory()->create();

        $response = $this->updateRequest($event, $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_an_event()
    {
        $event = Event::factory()->create();

        $response = $this->get(route('events.edit', $event));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            EventsController::class,
            'update',
            UpdateRequest::class
        );
    }

    /** @test */
    public function a_guest_cannot_update_an_event()
    {
        $event = Event::factory()->create();

        $response = $this->patch(route('events.update', $event), $this->validParams());

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_past_event_cannot_be_edited()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = Event::factory()->past()->create();

        $response = $this->get(route('events.edit', $event));

        $response->assertForbidden();
    }

    /** @test */
    public function a_past_event_cannot_be_updated()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = Event::factory()->past()->create();

        $response = $this->patch(route('events.update', $event), $this->validParams());

        $response->assertForbidden();
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deletes_an_event_and_redirects($administrators)
    {
        $this->actAs($administrators);
        $event = Event::factory()->create();

        $this->deleteRequest($event);

        $this->assertSoftDeleted($event);
    }

    /** @test */
    public function a_basic_user_cannot_delete_an_event()
    {
        $this->actAs(Role::BASIC);
        $event = Event::factory()->create();

        $response = $this->deleteRequest($event);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_an_event()
    {
        $event = Event::factory()->create();

        $response = $this->deleteRequest($event);

        $response->assertRedirect(route('login'));
    }
}
