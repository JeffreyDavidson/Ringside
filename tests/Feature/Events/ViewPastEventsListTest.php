<?php

namespace Tests\Feature\Events;

use Tests\TestCase;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewPastEventsListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_inactive_wrestlers()
    {
        $this->actAs('administrator');
        $pastEvents = factory(Event::class, 3)->states('past')->create();
        $scheduledEvent = factory(Event::class)->states('scheduled')->create();
        $archivedEvent = factory(Event::class)->states('archived')->create();

        $response = $this->get(route('events.index', ['state' => 'past']));

        $response->assertOk();
        $response->assertSee(e($pastEvents[0]->name));
        $response->assertSee(e($pastEvents[1]->name));
        $response->assertSee(e($pastEvents[2]->name));
        $response->assertDontSee(e($scheduledEvent->name));
        $response->assertDontSee(e($archivedEvent->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_active_wrestlers()
    {
        $this->actAs('basic-user');
        factory(Event::class)->states('past')->create();

        $response = $this->get(route('events.index', ['state' => 'past']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_active_wrestlers()
    {
        factory(Event::class)->states('past')->create();

        $response = $this->get(route('events.index', ['state' => 'past']));

        $response->assertRedirect('/login');
    }
}
