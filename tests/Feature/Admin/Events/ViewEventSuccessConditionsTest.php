<?php

namespace Tests\Feature\Admin\Events;

use Tests\TestCase;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group admins
 */
class ViewEventSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_an_event()
    {
        $this->actAs('administrator');
        $event = factory(Event::class)->create();

        $response = $this->get(route('events.show', $event));

        $response->assertViewIs('events.show');
        $this->assertTrue($response->data('event')->is($event));
    }
}
