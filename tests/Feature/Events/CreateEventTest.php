<?php

namespace Tests\Feature\Events;

use Tests\TestCase;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
            'date' => today()->toDateTimeString(),
            'venue_id' => factory(Venue::class)->create()->id,
            'preview' => 'This is an event preview.',
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_creating_an_event()
    {
        $this->actAs('administrator');

        $response = $this->get(route('events.create'));

        $response->assertViewIs('events.create');
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_an_event()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('events.create'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_an_event()
    {
        $response = $this->get(route('events.create'));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_create_an_event()
    {
        $this->actAs('administrator');

        $response = $this->post(route('events.store'), $this->validParams());

        $response->assertRedirect(route('events.index'));
        tap(Event::first(), function ($event) {
            $this->assertEquals('Example Event Name', $event->name);
            $this->assertEquals(today()->toDateTimeString(), $event->date);
            $this->assertEquals(1, $event->venue_id);
            $this->assertEquals('This is an event preview.', $event->preview);
        });
    }

    /** @test */
    public function an_event_slug_is_generated_when_created()
    {
        $this->actAs('administrator');

        $response = $this->post(route('events.store'), $this->validParams());

        tap(Event::first(), function ($event) {
            $this->assertEquals('example-event-name', $event->slug);
        });
    }

    /** @test */
    public function a_basic_user_cannot_create_an_event()
    {
        $this->actAs('basic-user');

        $response = $this->post(route('events.store'), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_create_an_event()
    {
        $response = $this->post(route('events.store'), $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_event_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('events.store'), $this->validParams([
            'name' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function an_event_date_date_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('events.store'), $this->validParams([
            'date' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('date');
    }

    /** @test */
    public function an_event_date_must_be_in_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->post(route('events.store'), $this->validParams([
            'date' => today()->toDateString()
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('date');
    }

    /** @test */
    public function an_event_venue_id_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('events.store'), $this->validParams([
            'venue_id' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('venue_id');
    }

    /** @test */
    public function an_event_venue_id_must_be_an_integer()
    {
        $this->actAs('administrator');

        $response = $this->post(route('events.store'), $this->validParams([
            'venue_id' => 'not-an-integer'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('venue_id');
    }

    /** @test */
    public function an_event_venue_id_must_exist()
    {
        $this->actAs('administrator');

        $response = $this->post(route('events.store'), $this->validParams([
            'venue_id' => 999
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('venue_id');
    }

    /** @test */
    public function an_event_preview_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('events.store'), $this->validParams([
            'preview' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('preview');
    }
}
