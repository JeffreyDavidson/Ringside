<?php

namespace Tests\Feature\SuperAdmin\Events;

use Tests\TestCase;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group superadmins
 */
class RestoreEventSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_restore_a_deleted_event()
    {
        $this->actAs('super-administrator');
        $event = factory(Event::class)->states('scheduled')->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->put(route('events.restore', $event));

        $response->assertRedirect(route('events.index'));
        $this->assertNull($event->fresh()->deleted_at);
    }
}
