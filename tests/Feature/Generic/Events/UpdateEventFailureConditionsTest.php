<?php

namespace Tests\Feature\Generic\Events;

use Tests\TestCase;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group generics
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
            'date' => today()->toDateTimeString(),
            'venue_id' => factory(Venue::class)->create()->id,
            'preview' => 'This is an event preview.',
        ], $overrides);
    }

    /** @test */
    public function a_past_event_cannot_be_edited()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('past')->create();

        $response = $this->get(route('events.edit', $event));

        $response->assertForbidden();
    }

    /** @test */
    public function a_past_event_cannot_be_updated()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('past')->create();

        $response = $this->patch(route('events.update', $event), $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function an_event_name_must_be_a_string_if_filled()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('scheduled')->create();

        $response = $this->from(route('events.edit', $event))
                        ->patch(route('events.update', $event), $this->validParams([
                            'name' => ['not-a-string']
                        ]));

        $response->assertRedirect(route('events.edit', $event));
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function an_event_name_must_be_unique()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('scheduled')->create();
        factory(Event::class)->states('past')->create(['name' => 'Example Event Name']);

        $response = $this->from(route('events.edit', $event))
                        ->patch(route('events.update', $event), $this->validParams([
                            'name' => 'Example Event Name'
                        ]));

        $response->assertRedirect(route('events.edit', $event));
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function an_event_date_must_be_a_string_if_filled()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('scheduled')->create();

        $response = $this->from(route('events.edit', $event))
                        ->patch(route('events.update', $event), $this->validParams([
                            'date' => ['not-a-string']
                        ]));

        $response->assertRedirect(route('events.edit', $event));
        $response->assertSessionHasErrors('date');
    }

    /** @test */
    public function an_event_date_must_be_in_datetime_format_if_filled()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('scheduled')->create();

        $response = $this->from(route('events.edit', $event))
                        ->patch(route('events.update', $event), $this->validParams([
                            'date' => now()->toDateString()
                        ]));

        $response->assertRedirect(route('events.edit', $event));
        $response->assertSessionHasErrors('date');
    }

    /** @test */
    public function an_event_venue_id_must_be_an_integer_if_filled()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('scheduled')->create();

        $response = $this->from(route('events.edit', $event))
                        ->patch(route('events.update', $event), $this->validParams([
                            'venue_id' => 'not-an_integer'
                        ]));

        $response->assertRedirect(route('events.edit', $event));
        $response->assertSessionHasErrors('venue_id');
    }

    /** @test */
    public function an_event_venue_id_must_exist_if_filled()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('scheduled')->create();

        $response = $this->from(route('events.edit', $event))
                        ->patch(route('events.update', $event), $this->validParams([
                            'venue_id' => 999
                        ]));

        $response->assertRedirect(route('events.edit', $event));
        $response->assertSessionHasErrors('venue_id');
    }
}
