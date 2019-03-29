<?php

namespace Tests\Feature\Events;

use Tests\TestCase;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewArchivedEventsListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_archived_events()
    {
        $this->actAs('administrator');
        $archivedEvents = factory(Event::class, 3)->states('archived')->create();
        $scheduledEvent = factory(Event::class)->states('scheduled')->create();

        $response = $this->get(route('events.index', ['state' => 'archived']));

        $response->assertOk();
        $response->assertSee(e($archivedEvents[0]->name));
        $response->assertSee(e($archivedEvents[1]->name));
        $response->assertSee(e($archivedEvents[2]->name));
        $response->assertDontSee(e($scheduledEvent->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_archived_events()
    {
        $this->actAs('basic-user');
        factory(Event::class)->states('archived')->create();

        $response = $this->get(route('events.index', ['state' => 'archived']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_archived_events()
    {
        factory(Event::class)->states('archived')->create();

        $response = $this->get(route('events.index', ['state' => 'archived']));

        $response->assertRedirect('/login');
    }
}
