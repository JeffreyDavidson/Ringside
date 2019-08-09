<?php

namespace Tests\Feature\Guest\Events;

use Tests\TestCase;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group guests
 */
class DeleteEventFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_delete_an_event()
    {
        $event = factory(Event::class)->create();

        $response = $this->delete(route('events.destroy', $event));

        $response->assertRedirect(route('login'));
    }
}
