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
class ViewEventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function administrators_can_view_an_event_page($administrators)
    {
        $this->actAs($administrators);
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
