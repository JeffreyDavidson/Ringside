<?php

namespace Tests\Feature\Guest\Events;

use Tests\TestCase;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group guests
 */
class ViewEventFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_view_an_event()
    {
        $event = factory(Event::class)->create();

        $response = $this->get(route('events.show', $event));

        $response->assertRedirect(route('login'));
    }
}
