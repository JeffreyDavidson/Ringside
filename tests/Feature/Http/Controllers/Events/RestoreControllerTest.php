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
class RestoreControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_restores_a_soft_deleted_event_and_redirects($administrators)
    {
        $event = Event::factory()->softDeleted()->create();

        $this->actAs($administrators)
            ->patch(route('events.restore', $event))
            ->assertRedirect(route('events.index'));

        $this->assertNull($event->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_event()
    {
        $event = Event::factory()->softDeleted()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('events.restore', $event))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_event()
    {
        $event = Event::factory()->softDeleted()->create();

        $this->patch(route('events.restore', $event))
            ->assertRedirect(route('login'));
    }
}
