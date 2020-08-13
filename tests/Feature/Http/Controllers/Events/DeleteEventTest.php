<?php

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\EventFactory;
use Tests\TestCase;

/**
 * @group events
 */
class DeleteEventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_scheduled_event()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = EventFactory::new()->scheduled()->create();

        $this->deleteRequest($event);

        $this->assertSoftDeleted($event);
    }

    /** @test */
    public function an_administrator_can_delete_a_past_event()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = EventFactory::new()->past()->create();

        $this->deleteRequest($event);

        $this->assertSoftDeleted($event);
    }

    /** @test */
    public function a_basic_user_cannot_delete_an_event()
    {
        $this->actAs(Role::BASIC);
        $event = EventFactory::new()->create();

        $response = $this->deleteRequest($event);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_an_event()
    {
        $event = EventFactory::new()->create();

        $response = $this->deleteRequest($event);

        $response->assertRedirect(route('login'));
    }
}
