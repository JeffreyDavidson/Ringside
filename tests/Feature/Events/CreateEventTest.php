<?php

namespace Tests\Feature\Events;

use App\Enums\Role;
use Tests\TestCase;
use App\Models\Event;
use Tests\Factories\EventFactory;
use Tests\Factories\VenueFactory;
use App\Http\Requests\Events\StoreRequest;
use App\Http\Controllers\Events\EventsController;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 */
class CreateEventTest extends TestCase
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
            'date' => now()->toDateTimeString(),
            'venue_id' => VenueFactory::new()->create()->id,
            'preview' => 'This is an event preview.',
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_creating_an_event()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->createRequest('events');

        $response->assertViewIs('events.create');
        $response->assertViewHas('event', new Event);
    }

    /** @test */
    public function an_administrator_can_create_an_event()
    {
        $this->withoutExceptionHandling();
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->from(route('events.create'))
                        ->post(route('events.store'), $this->validParams());

        $response->assertRedirect(route('events.index'));
        tap(Event::first(), function ($event) {
            $this->assertEquals('Example Event Name', $event->name);
            $this->assertEquals(now()->toDateTimeString(), $event->date);
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
    public function a_basic_user_cannot_view_the_form_for_creating_an_event()
    {
        $this->actAs(Role::BASIC);

        $response = $this->createRequest('events');

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_an_event()
    {
        $this->actAs(Role::BASIC);

        $response = $this->storeRequest('events', $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_an_event()
    {
        $response = $this->get(route('events.create'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_create_an_event()
    {
        $response = $this->post(route('events.store'), $this->validParams());

        $response->assertRedirect(route('login'));
    }
}
