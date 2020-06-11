<?php

namespace Tests\Feature\Events;

use App\Enums\Role;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group events
 * @group admins
 */
class ViewEventsListSuccessConditionsTest extends TestCase
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

        $scheduled = factory(Event::class, 3)->states('scheduled')->create();
        $past = factory(Event::class, 3)->states('past')->create();

        $this->events = collect([
            'scheduled' => $scheduled,
            'past'      => $past,
            'all'       => collect()
                        ->concat($scheduled)
                        ->concat($past),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_events_page()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->get(route('events.index'));

        $response->assertOk();
        $response->assertViewIs('events.index');
    }

    /** @test */
    public function an_administrator_can_view_all_events()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('events.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->events->get('all')->count(),
            'data'         => $this->events->get('all')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_scheduled_events()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('events.index', ['status' => 'scheduled']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->events->get('scheduled')->count(),
            'data'         => $this->events->get('scheduled')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_past_events()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('events.index', ['status' => 'past']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->events->get('past')->count(),
            'data'         => $this->events->get('past')->only(['id'])->toArray(),
        ]);
    }
}
