<?php

namespace Tests\Feature\Events;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\EventFactory;
use Tests\TestCase;

/**
 * @group events
 */
class RestoreEventSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_restore_a_deleted_event()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = EventFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($event);

        $response->assertRedirect(route('events.index'));
        $this->assertNull($event->fresh()->deleted_at);
    }
}
