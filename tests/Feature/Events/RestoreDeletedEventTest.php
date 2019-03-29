<?php

namespace Tests\Feature\Events;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RestoreDeletedEventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_restore_a_deleted_event()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->states('scheduled')->create(['deleted_at' => Carbon::yesterday()->toDateTimeString()]);

        $response = $this->patch(route('events.restore', $event));

        $response->assertRedirect(route('events.index'));
        $this->assertNull($event->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_event()
    {
        $this->actAs('basic-user');
        $event = factory(Event::class)->states('scheduled')->create(['deleted_at' => Carbon::yesterday()->toDateTimeString()]);

        $response = $this->patch(route('events.restore', $event));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_restore_a_deleted_event()
    {
        $event = factory(Event::class)->states('scheduled')->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('events.restore', $event));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_deleted_event_cannot_be_restored()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->create();

        $response = $this->patch(route('events.restore', $event));

        $response->assertStatus(404);
    }
}
