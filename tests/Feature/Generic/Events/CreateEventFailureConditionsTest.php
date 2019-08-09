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
            'venue_id' => factory(Venue::class)->create()->id,
            'preview' => 'This is an event preview.',
        ], $overrides);
    }

    /** @test */
    public function an_event_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->from(route('events.create'))
                        ->post(route('events.store'), $this->validParams([
                            'name' => ''
                        ]));

        $response->assertRedirect(route('events.create'));
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function an_event_name_must_be_a_string()
    {
        $this->actAs('administrator');

        $response = $this->from(route('events.create'))
                        ->post(route('events.store'), $this->validParams([
                            'name' => []
                        ]));

        $response->assertRedirect(route('events.create'));
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function an_event_name_must_be_unique()
    {
        $this->actAs('administrator');
        factory(Event::class)->create(['name' => 'Example Event Name']);

        $response = $this->from(route('events.create'))
                        ->post(route('events.store'), $this->validParams([
                            'name' => 'Example Event Name'
                        ]));

        $response->assertRedirect(route('events.create'));
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function an_event_date_must_be_a_string_if_present()
    {
        $this->actAs('administrator');

        $response = $this->from(route('events.create'))
                        ->post(route('events.store'), $this->validParams([
                            'date' => []
                        ]));

        $response->assertRedirect(route('events.create'));
        $response->assertSessionHasErrors('date');
    }

    /** @test */
    public function an_event_date_must_be_in_datetime_format_if_filled()
    {
        $this->actAs('administrator');

        $response = $this->from(route('events.create'))
                        ->post(route('events.store'), $this->validParams([
                            'date' => now()->toDateString()
                        ]));

        $response->assertRedirect(route('events.create'));
        $response->assertSessionHasErrors('date');
    }

    /** @test */
    public function an_event_venue_id_must_be_an_integer_if_filled()
    {
        $this->actAs('administrator');

        $response = $this->from(route('events.create'))
                        ->post(route('events.store'), $this->validParams([
                            'venue_id' => 'not-an-integer'
                        ]));

        $response->assertRedirect(route('events.create'));
        $response->assertSessionHasErrors('venue_id');
    }

    /** @test */
    public function an_event_venue_id_must_exist()
    {
        $this->actAs('administrator');

        $response = $this->from(route('events.create'))
                        ->post(route('events.store'), $this->validParams([
                            'venue_id' => 99999999
                        ]));

        $response->assertRedirect(route('events.create'));
        $response->assertSessionHasErrors('venue_id');
    }
}
