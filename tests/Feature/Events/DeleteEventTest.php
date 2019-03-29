<?php

namespace Tests\Feature\Events;

use Tests\TestCase;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeleteEventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_scheduled_event()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('scheduled')->create();

        $response = $this->delete(route('events.destroy', $event));

        $this->assertSoftDeleted('events', ['name' => $event->name]);
    }

    /** @test */
    public function a_basic_user_cannot_delete_an_event()
    {
        $this->actAs('basic-user');
        $event = factory(Event::class)->create();

        $response = $this->delete(route('events.destroy', $event));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_delete_an_event()
    {
        $event = factory(Event::class)->create();

        $response = $this->delete(route('events.destroy', $event));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_event_that_is_not_scheduled_cannot_be_deleted()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('archived')->create();

        $response = $this->delete(route('events.destroy', $event));

        $response->assertStatus(403);
    }
}
