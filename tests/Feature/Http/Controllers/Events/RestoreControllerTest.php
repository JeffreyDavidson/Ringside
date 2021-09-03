<?php

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\Role;
use App\Http\Controllers\Events\EventsController;
use App\Http\Controllers\Events\RestoreController;
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

    public Event $event;

    public function setUp(): void
    {
        parent::setUp();

        $this->event = Event::factory()->softDeleted()->create();
    }

    /**
     * @test
     */
    public function invoke_restores_a_soft_deleted_event_and_redirects()
    {
        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([RestoreController::class], $this->event))
            ->assertRedirect(action([EventsController::class, 'index']));

        $this->assertNull($this->event->fresh()->deleted_at);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_restore_a_deleted_event()
    {
        $this->actAs(Role::BASIC)
            ->patch(action([RestoreController::class], $this->event))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_restore_a_event()
    {
        $this->patch(action([RestoreController::class], $this->event))
            ->assertRedirect(route('login'));
    }
}
