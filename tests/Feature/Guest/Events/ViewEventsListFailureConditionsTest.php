<?php

namespace Tests\Feature\Guest\Events;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group guests
 */
class ViewEventListFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_view_events_page()
    {
        $response = $this->get(route('events.index'));

        $response->assertRedirect(route('login'));
    }
}
