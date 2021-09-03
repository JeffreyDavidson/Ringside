<?php

namespace Tests\Integration\Models;

use App\Models\Event;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group event
 * @group models
 */
class EventTest extends TestCase
{
    use RefreshDatabase;

    private $scheduledEvent;
    private $unscheduledEvent;
    private $pastEvent;

    public function setUp(): void
    {
        parent::setUp();

        $this->scheduledEvent = Event::factory()->scheduled()->create();
        $this->unscheduledEvent = Event::factory()->unscheduled()->create();
        $this->pastEvent = Event::factory()->past()->create();
    }

    /**
     * @test
     */
    public function a_event_has_a_name()
    {
        $event = Event::factory()->create(['name' => 'Example Event Name']);

        $this->assertEquals('Example Event Name', $event->name);
    }

    /**
     * @test
     */
    public function a_event_has_a_date()
    {
        $event = Event::factory()->create(['date' => '2020-03-05 00:00:00']);

        $this->assertEquals('2020-03-05 00:00:00', $event->date);
    }

    /**
     * @test
     */
    public function an_event_has_a_venue()
    {
        $venue = Venue::factory()->create();
        $event = Event::factory()->create(['venue_id' => $venue->id]);

        $this->assertInstanceOf(Venue::class, $event->venue);
        $this->assertTrue($event->venue->is($venue));
    }

    /**
     * @test
     */
    public function it_can_get_scheduled_events()
    {
        $scheduledEvents = Event::scheduled()->get();

        $this->assertCount(1, $scheduledEvents);
        $this->assertCollectionHas($scheduledEvents, $this->scheduledEvent);
        $this->assertCollectionDoesntHave($scheduledEvents, $this->unscheduledEvent);
        $this->assertCollectionDoesntHave($scheduledEvents, $this->pastEvent);
    }

    /**
     * @test
     */
    public function it_can_get_unscheduled_events()
    {
        $unscheduledEvents = Event::unscheduled()->get();

        $this->assertCount(1, $unscheduledEvents);
        $this->assertCollectionHas($unscheduledEvents, $this->unscheduledEvent);
        $this->assertCollectionDoesntHave($unscheduledEvents, $this->scheduledEvent);
        $this->assertCollectionDoesntHave($unscheduledEvents, $this->pastEvent);
    }

    /**
     * @test
     */
    public function it_can_get_past_events()
    {
        $pastEvents = Event::past()->get();

        $this->assertCount(1, $pastEvents);
        $this->assertCollectionHas($pastEvents, $this->pastEvent);
        $this->assertCollectionDoesntHave($pastEvents, $this->scheduledEvent);
        $this->assertCollectionDoesntHave($pastEvents, $this->unscheduledEvent);
    }

    /**
     * @test
     */
    public function an_event_with_a_date_in_the_future_is_scheduled()
    {
        $event = Event::factory()->create(['date' => Carbon::parse('+2 weeks')]);

        $this->assertTrue($event->isScheduled());
    }

    /**
     * @test
     */
    public function an_event_without_a_date_is_unscheduled()
    {
        $event = Event::factory()->create(['date' => null]);

        $this->assertTrue($event->isUnscheduled());
    }

    /**
     * @test
     */
    public function an_event_with_a_date_in_the_past_has_past()
    {
        $event = Event::factory()->create(['date' => Carbon::parse('-2 weeks')]);

        $this->assertTrue($event->isPast());
    }
}
