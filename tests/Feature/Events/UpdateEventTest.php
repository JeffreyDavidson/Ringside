<?php

namespace Tests\Feature\Events;

use Tests\TestCase;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateEventTest extends TestCase
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
    public function an_administrator_can_view_the_form_for_editing_an_event()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->create();

        $response = $this->get(route('events.edit', $event));

        $response->assertViewIs('events.edit');
        $this->assertTrue($response->data('event')->is($event));
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_an_event()
    {
        $this->actAs('basic-user');
        $event = factory(Event::class)->create();

        $response = $this->get(route('events.edit', $event));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_an_event()
    {
        $event = factory(Event::class)->create();

        $response = $this->get(route('events.edit', $event));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_update_an_event()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->create();

        $response = $this->patch(route('events.update', $event), $this->validParams());

        $response->assertRedirect(route('events.index'));
        tap($event->fresh(), function ($event) {
            $this->assertEquals('Example Event Name', $event->name);
            $this->assertEquals(today()->toDateTimeString(), $event->date);
            $this->assertEquals('This is an event preview.', $event->preview);
        });
    }

    /** @test */
    public function a_basic_user_cannot_update_an_event()
    {
        $this->actAs('basic-user');
        $event = factory(Event::class)->create();

        $response = $this->patch(route('events.update', $event), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_update_an_event()
    {
        $event = factory(Event::class)->create();

        $response = $this->patch(route('events.update', $event), $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_event_name_is_required()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->create();

        $response = $this->patch(route('events.update', $event), $this->validParams([
            'name' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function an_event_date_is_required()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->create();

        $response = $this->patch(route('events.update', $event), $this->validParams([
            'date' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('date');
    }

    /** @test */
    public function an_event_date_must_be_in_datetime_format()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->create();

        $response = $this->patch(route('events.update', $event), $this->validParams([
            'date' => today()->toDateString()
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('date');
    }

    /** @test */
    public function an_event_date_must_be_a_datetime_format()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->create();

        $response = $this->patch(route('events.update', $event), $this->validParams([
            'date' => 'not-a-datetime'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('date');
    }

    /** @test */
    public function an_event_venue_id_is_required()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->create();

        $response = $this->patch(route('events.update', $event), $this->validParams([
            'venue_id' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('venue_id');
    }

    /** @test */
    public function an_event_venue_id_must_be_an_integer()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->create();

        $response = $this->patch(route('events.update', $event), $this->validParams([
            'venue_id' => 'not-an_integer'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('venue_id');
    }

    /** @test */
    public function an_event_venue_id_must_exist()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->create();

        $response = $this->patch(route('events.update', $event), $this->validParams([
            'venue_id' => 999
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('venue_id');
    }

    /** @test */
    public function an_event_preview_is_required()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->create();

        $response = $this->patch(route('events.update', $event), $this->validParams([
            'preview' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('preview');
    }
}
