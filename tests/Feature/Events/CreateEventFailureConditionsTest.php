<?php

namespace Tests\Feature\Events;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\VenueFactory;
use Tests\TestCase;

/**
 * @group events
 */
class CreateEventFailureConditionsTest extends TestCase
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

    /** @test */
    public function an_event_name_is_required()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->from(route('events.create'))
                        ->post(route('events.store'), $this->validParams([
                            'name' => '',
                        ]));

        $response->assertRedirect(route('events.create'));
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function an_event_name_must_be_a_string()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->from(route('events.create'))
                        ->post(route('events.store'), $this->validParams([
                            'name' => [],
                        ]));

        $response->assertRedirect(route('events.create'));
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function an_event_name_must_be_unique()
    {
        $this->actAs(Role::ADMINISTRATOR);
        factory(Event::class)->create(['name' => 'Example Event Name']);

        $response = $this->from(route('events.create'))
                        ->post(route('events.store'), $this->validParams([
                            'name' => 'Example Event Name',
                        ]));

        $response->assertRedirect(route('events.create'));
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function an_event_date_must_be_a_string_if_present()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->from(route('events.create'))
                        ->post(route('events.store'), $this->validParams([
                            'date' => [],
                        ]));

        $response->assertRedirect(route('events.create'));
        $response->assertSessionHasErrors('date');
    }

    /** @test */
    public function an_event_date_must_be_in_datetime_format_if_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->from(route('events.create'))
                        ->post(route('events.store'), $this->validParams([
                            'date' => now()->toDateString(),
                        ]));

        $response->assertRedirect(route('events.create'));
        $response->assertSessionHasErrors('date');
    }

    /** @test */
    public function an_event_venue_id_must_be_an_integer_if_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->from(route('events.create'))
                        ->post(route('events.store'), $this->validParams([
                            'venue_id' => 'not-an-integer',
                        ]));

        $response->assertRedirect(route('events.create'));
        $response->assertSessionHasErrors('venue_id');
    }

    /** @test */
    public function an_event_venue_id_must_exist()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->from(route('events.create'))
                        ->post(route('events.store'), $this->validParams([
                            'venue_id' => 99999999,
                        ]));

        $response->assertRedirect(route('events.create'));
        $response->assertSessionHasErrors('venue_id');
    }
}
