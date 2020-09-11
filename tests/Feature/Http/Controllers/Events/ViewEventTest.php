<?php

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\Role;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group events
 */
class ViewEventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_view_an_event_page($adminRoles)
    {
        $this->actAs($adminRoles);
        $event = Event::factory()->create();

        $response = $this->showRequest($event);

        $response->assertViewIs('events.show');
        $this->assertTrue($response->data('event')->is($event));
    }

    /** @test */
    public function a_basic_user_cannot_view_an_event_page()
    {
        $this->actAs(Role::BASIC);
        $event = Event::factory()->create();

        $response = $this->showRequest($event);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_an_event_page()
    {
        $event = Event::factory()->create();

        $response = $this->showRequest($event);

        $response->assertRedirect(route('login'));
    }
}
