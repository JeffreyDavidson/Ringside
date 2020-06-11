<?php

namespace Tests\Feature\Events;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\EventFactory;
use Tests\TestCase;

/**
 * @group events
 */
class DeleteEventFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

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
