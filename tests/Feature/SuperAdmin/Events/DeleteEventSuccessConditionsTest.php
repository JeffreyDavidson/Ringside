<?php

namespace Tests\Feature\SuperAdmin\Events;

use Tests\TestCase;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group superadmins
 */
class DeleteEventSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_delete_a_scheduled_event()
    {
        $this->actAs('super-administrator');
        $event = factory(Event::class)->states('scheduled')->create();

        $this->delete(route('events.destroy', $event));

        $this->assertSoftDeleted('events', ['name' => $event->name]);
    }

    /** @test */
    public function a_super_administrator_can_delete_a_past_event()
    {
        $this->actAs('super-administrator');
        $event = factory(Event::class)->states('past')->create();

        $this->delete(route('events.destroy', $event));

        $this->assertSoftDeleted('events', ['name' => $event->name]);
    }
}
