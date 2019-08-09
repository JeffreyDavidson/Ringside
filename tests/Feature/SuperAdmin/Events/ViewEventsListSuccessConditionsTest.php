<?php

namespace Tests\Feature\SuperAdmin\Events;

use Tests\TestCase;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group superadmins
 */
class ViewScheduledEventListSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @var \Illuminate\Support\Collection */
    protected $events;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $mapToIdAndName = function (Event $event) {
            return [
                'id' => $event->id,
                'name' => e($event->name),
            ];
        };

        $scheduled  = factory(Event::class, 3)->states('scheduled')->create()->map($mapToIdAndName);
        $past       = factory(Event::class, 3)->states('past')->create()->map($mapToIdAndName);

        $this->events = collect([
            'scheduled' => $scheduled,
            'past'      => $past,
            'all'       => collect()
                        ->concat($scheduled)
                        ->concat($past)
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_events_page()
    {
        $this->actAs('super-administrator');

        $response = $this->get(route('events.index'));

        $response->assertOk();
        $response->assertViewIs('events.index');
    }

    /** @test */
    public function a_super_administrator_can_view_all_events()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('events.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->events->get('all')->count(),
            'data'         => $this->events->get('all')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_scheduled_events()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('events.index', ['status' => 'only_scheduled']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->events->get('scheduled')->count(),
            'data'         => $this->events->get('scheduled')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_past_events()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('events.index', ['status' => 'only_past']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->events->get('past')->count(),
            'data'         => $this->events->get('past')->toArray(),
        ]);
    }
}
