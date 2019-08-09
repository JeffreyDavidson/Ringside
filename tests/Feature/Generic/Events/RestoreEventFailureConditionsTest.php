<?php

namespace Tests\Feature\Generic\Events;

use Tests\TestCase;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group generics
 */
class RestoreEventFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_scheduled_event_cannot_be_restored()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('scheduled')->create();

        $response = $this->put(route('events.restore', $event));

        $response->assertNotFound();
    }

    /** @test */
    public function a_past_event_cannot_be_restored()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('past')->create();

        $response = $this->put(route('events.restore', $event));

        $response->assertNotFound();
    }
}
