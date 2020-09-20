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
class DeleteEventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_scheduled_event()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = Event::factory()->scheduled()->create();

        $this->deleteRequest($event);

        $this->assertSoftDeleted($event);
    }

    /** @test */
    public function an_administrator_can_delete_a_past_event()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = Event::factory()->past()->create();

        $this->deleteRequest($event);

        $this->assertSoftDeleted($event);
    }

    /** @test */
    public function a_basic_user_cannot_delete_an_event()
    {
        $this->actAs(Role::BASIC);
        $event = Event::factory()->create();

        $response = $this->deleteRequest($event);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_an_event()
    {
        $event = Event::factory()->create();

        $response = $this->deleteRequest($event);

        $response->assertRedirect(route('login'));
    }
}
