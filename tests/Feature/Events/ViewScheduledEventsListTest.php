<?php

namespace Tests\Feature\Events;

use Tests\TestCase;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewScheduledEventListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_scheduled_events()
    {
        $this->actAs('administrator');
        $scheduledEvents = factory(Event::class, 3)->states('scheduled')->create();
        $archivedEvent = factory(Event::class)->states('archived')->create();

        $response = $this->get(route('events.index'));

        $response->assertOk();
        $response->assertSee(e($scheduledEvents[0]->name));
        $response->assertSee(e($scheduledEvents[1]->name));
        $response->assertSee(e($scheduledEvents[2]->name));
        $response->assertDontSee(e($archivedEvent->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_scheduled_events()
    {
        $this->actAs('basic-user');
        factory(Event::class)->states('scheduled')->create();

        $response = $this->get(route('events.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_scheduled_events()
    {
        factory(Event::class)->states('scheduled')->create();

        $response = $this->get(route('events.index'));

        $response->assertRedirect('/login');
    }
}
