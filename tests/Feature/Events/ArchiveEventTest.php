<?php

namespace Tests\Feature\Events;

use Tests\TestCase;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArchiveEventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_archive_a_past_event()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('past')->create();

        $response = $this->post(route('events.archive', $event));

        $response->assertRedirect(route('events.index', ['state' => 'archived']));
        tap($event->fresh(), function ($event) {
            $this->assertNotNull($event->archived_at);
        });
    }

    /** @test */
    public function a_basic_user_cannot_archive_an_event()
    {
        $this->actAs('basic-user');
        $event = factory(Event::class)->states('past')->create();

        $response = $this->post(route('events.archive', $event));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_archive_an_event()
    {
        $event = factory(Event::class)->states('past')->create();

        $response = $this->post(route('events.archive', $event));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_scheduled_event_cannot_be_archived()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('scheduled')->create();

        $response = $this->post(route('events.archive', $event));

        $response->assertStatus(403);
    }

    /** @test */
    public function an_archived_event_cannot_be_archived()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('archived')->create();

        $response = $this->post(route('events.archive', $event));

        $response->assertStatus(403);
    }
}
