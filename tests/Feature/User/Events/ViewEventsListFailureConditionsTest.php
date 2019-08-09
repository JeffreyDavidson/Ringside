<?php

namespace Tests\Feature\User\Events;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group users
 */
class ViewEventListFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_events_page()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('events.index'));

        $response->assertForbidden();
    }
}
