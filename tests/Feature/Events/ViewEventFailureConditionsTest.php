<?php

namespace Tests\Feature\Events;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\EventFactory;
use Tests\TestCase;

/**
 * @group events
 */
class ViewEventFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_an_event_page()
    {
        $this->actAs(Role::BASIC);
        $event = EventFactory::new()->create();

        $response = $this->showRequest($event);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_an_event_page()
    {
        $event = EventFactory::new()->create();

        $response = $this->showRequest($event);

        $response->assertRedirect(route('login'));
    }
}
