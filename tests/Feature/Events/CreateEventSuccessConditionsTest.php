<?php

namespace Tests\Feature\Events;

use App\Enums\Role;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group events
 */
class CreateEventSuccessConditionsTest extends TestCase
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
    public function an_event_date_is_optional()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->post(route('events.store'), $this->validParams([
            'date' => '',
        ]));

        $response->assertSessionDoesntHaveErrors('date');
    }

    /** @test */
    public function an_event_venue_id_is_optional()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->post(route('events.store'), $this->validParams([
            'venue_id' => '',
        ]));

        $response->assertSessionDoesntHaveErrors('venue_id');
    }

    /** @test */
    public function an_event_preview_is_optional()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->post(route('events.store'), $this->validParams([
            'preview' => '',
        ]));

        $response->assertSessionDoesntHaveErrors('preview');
    }
}
