<?php

namespace Tests\Feature\SuperAdmin\Events;

use Tests\TestCase;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group superadmins
 */
class ViewEventSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_view_an_event()
    {
        $this->actAs('super-administrator');
        $event = factory(Event::class)->create();

        $response = $this->get(route('events.show', $event));

        $response->assertViewIs('events.show');
        $this->assertTrue($response->data('event')->is($event));
    }
}
