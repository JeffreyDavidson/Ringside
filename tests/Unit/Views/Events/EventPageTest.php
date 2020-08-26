<?php

namespace Tests\Unit\Views\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NunoMaduro\LaravelMojito\InteractsWithViews;
use Tests\Factories\EventFactory;
use Tests\Factories\VenueFactory;
use Tests\TestCase;

/**
 * @group views
 */
class EventPageTest extends TestCase
{
    use RefreshDatabase, InteractsWithViews;

    /** @test */
    public function an_events_name_can_be_seen_on_the_event_page()
    {
        $event = EventFactory::new()->create(['name' => 'Event 1']);

        $this->assertView('events.show', compact('event'))->contains('Event 1');
    }

    /** @test */
    public function an_events_date_can_be_seen_on_the_event_page()
    {
        $event = EventFactory::new()->create(['date' => '2020-03-05']);

        $this->assertView('events.show', compact('event'))->contains('March 5, 2020');
    }

    /** @test */
    public function an_events_venue_can_be_seen_on_the_event_page()
    {
        $event = EventFactory::new()
            ->atVenue(
                VenueFactory::new(['name' => 'The Awesome Arena'])
            )
            ->create();

        $this->assertView('events.show', compact('event'))->contains('The Awesome Arena');
    }

    /** @test */
    public function an_events_preview_can_be_seen_on_the_event_page()
    {
        $event = EventFactory::new()->create(['preview' => 'This is an example event preview.']);

        $this->assertView('events.show', compact('event'))->contains('This is an example event preview.');
    }
}
