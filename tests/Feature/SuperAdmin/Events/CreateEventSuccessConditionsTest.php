<?php

namespace Tests\Feature\SuperAdmin\Events;

use Tests\TestCase;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group superadmins
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
    public function a_super_administrator_can_view_the_form_for_creating_an_event()
    {
        $this->actAs('super-administrator');

        $response = $this->get(route('events.create'));

        $response->assertViewIs('events.create');
        $response->assertViewHas('event', new Event);
    }

    /** @test */
    public function a_super_administrator_can_create_an_event()
    {
        $this->actAs('super-administrator');

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
}
