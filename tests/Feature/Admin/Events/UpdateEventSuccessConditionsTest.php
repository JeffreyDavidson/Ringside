<?php

namespace Tests\Feature\Admin\Events;

use Tests\TestCase;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group admins
 */
class UpdateEventSuccessConditionsTest extends TestCase
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
    public function an_administrator_can_view_the_form_for_editing_a_scheduled_event()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('scheduled')->create();

        $response = $this->get(route('events.edit', $event));

        $response->assertViewIs('events.edit');
        $this->assertTrue($response->data('event')->is($event));
    }

    /** @test */
    public function an_administrator_can_update_a_scheduled_event()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('scheduled')->create();

        $response = $this->patch(route('events.update', $event), $this->validParams());

        $response->assertRedirect(route('events.index'));
        tap($event->fresh(), function ($event) {
            $this->assertEquals('Example Event Name', $event->name);
            $this->assertEquals(now()->toDateTimeString(), $event->date);
            $this->assertEquals('This is an event preview.', $event->preview);
        });
    }
}
