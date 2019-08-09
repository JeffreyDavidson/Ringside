<?php

namespace Tests\Feature\Events;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group generics
 */
class ViewEventSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_events_data_can_be_seen_on_their_profile()
    {
        $this->actAs('administrator');
        $venue = factory(Venue::class)->create();

        $event = factory(Event::class)->create([
            'name' => 'Event 1',
            'date' => Carbon::tomorrow()->toDateTimeString(),
            'venue_id' => 1,
            'preview' => 'This is an example event preview.',
        ]);

        $response = $this->get(route('events.show', ['event' => $event]));

        $response->assertSee('Event 1');
        $response->assertSee(Carbon::tomorrow()->toDateTimeString());
        $response->assertSee($venue->name);
        $response->assertSee('This is an example event preview.');
    }
}
