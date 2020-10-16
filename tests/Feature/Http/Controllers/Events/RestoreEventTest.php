<?php

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\Role;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group events
 * @group feature-events
 */
class RestoreEventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_restore_a_deleted_event()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = Event::factory()->softDeleted()->create();

        $response = $this->restoreRequest($event);

        $response->assertRedirect(route('events.index'));
        $this->assertNull($event->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_event()
    {
        $this->actAs(Role::BASIC);
        $event = Event::factory()->softDeleted()->create();

        $response = $this->restoreRequest($event);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_event()
    {
        $event = Event::factory()->softDeleted()->create();

        $response = $this->restoreRequest($event);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_non_soft_deleted_event_cannot_be_restored()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = Event::factory()->create(['deleted_at' => null]);

        $response = $this->restoreRequest($event);

        $response->assertNotFound();
    }
}