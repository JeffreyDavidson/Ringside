<?php

namespace Tests\Feature\Events;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\EventFactory;
use Tests\TestCase;

/**
 * @group events
 */
class DeleteEventSuccessConditionsTest extends TestCase
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

        $this->assertSoftDeleted($event->name);
    }
}
