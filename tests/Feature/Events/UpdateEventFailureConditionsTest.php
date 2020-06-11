<?php

namespace Tests\Feature\Events;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\EventFactory;
use Tests\Factories\VenueFactory;
use Tests\TestCase;

/**
 * @group events
 */
class UpdateEventFailureConditionsTest extends TestCase
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
    public function a_basic_user_cannot_view_the_form_for_editing_an_event()
    {
        $this->actAs(Role::BASIC);
        $event = EventFactory::new()->create();

        $response = $this->editRequest($event);

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_an_event()
    {
        $this->actAs(Role::BASIC);
        $event = EventFactory::new()->create();

        $response = $this->updateRequest($event, $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_an_event()
    {
        $event = factory(Event::class)->create();

        $response = $this->get(route('events.edit', $event));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_update_an_event()
    {
        $event = factory(Event::class)->create();

        $response = $this->patch(route('events.update', $event), $this->validParams());

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_past_event_cannot_be_edited()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = factory(Event::class)->states('past')->create();

        $response = $this->get(route('events.edit', $event));

        $response->assertForbidden();
    }

    /** @test */
    public function a_past_event_cannot_be_updated()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = factory(Event::class)->states('past')->create();

        $response = $this->patch(route('events.update', $event), $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function an_event_name_must_be_a_string_if_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = factory(Event::class)->states('scheduled')->create();

        $response = $this->from(route('events.edit', $event))
                        ->patch(route('events.update', $event), $this->validParams([
                            'name' => ['not-a-string'],
                        ]));

        $response->assertRedirect(route('events.edit', $event));
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function an_event_name_must_be_unique()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = factory(Event::class)->states('scheduled')->create();
        factory(Event::class)->states('past')->create(['name' => 'Example Event Name']);

        $response = $this->from(route('events.edit', $event))
                        ->patch(route('events.update', $event), $this->validParams([
                            'name' => 'Example Event Name',
                        ]));

        $response->assertRedirect(route('events.edit', $event));
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function an_event_date_must_be_a_string_if_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = factory(Event::class)->states('scheduled')->create();

        $response = $this->from(route('events.edit', $event))
                        ->patch(route('events.update', $event), $this->validParams([
                            'date' => ['not-a-string'],
                        ]));

        $response->assertRedirect(route('events.edit', $event));
        $response->assertSessionHasErrors('date');
    }

    /** @test */
    public function an_event_date_must_be_in_datetime_format_if_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = factory(Event::class)->states('scheduled')->create();

        $response = $this->from(route('events.edit', $event))
                        ->patch(route('events.update', $event), $this->validParams([
                            'date' => now()->toDateString(),
                        ]));

        $response->assertRedirect(route('events.edit', $event));
        $response->assertSessionHasErrors('date');
    }

    /** @test */
    public function an_event_venue_id_must_be_an_integer_if_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = factory(Event::class)->states('scheduled')->create();

        $response = $this->from(route('events.edit', $event))
                        ->patch(route('events.update', $event), $this->validParams([
                            'venue_id' => 'not-an_integer',
                        ]));

        $response->assertRedirect(route('events.edit', $event));
        $response->assertSessionHasErrors('venue_id');
    }

    /** @test */
    public function an_event_venue_id_must_exist_if_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = factory(Event::class)->states('scheduled')->create();

        $response = $this->from(route('events.edit', $event))
                        ->patch(route('events.update', $event), $this->validParams([
                            'venue_id' => 999,
                        ]));

        $response->assertRedirect(route('events.edit', $event));
        $response->assertSessionHasErrors('venue_id');
    }
}
