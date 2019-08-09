<?php

namespace Tests\Feature\User\Events;

use Tests\TestCase;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group users
 */
class DeleteEventFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_delete_an_event()
    {
        $this->actAs('basic-user');
        $event = factory(Event::class)->create();

        $response = $this->delete(route('events.destroy', $event));

        $response->assertForbidden();
    }
}
