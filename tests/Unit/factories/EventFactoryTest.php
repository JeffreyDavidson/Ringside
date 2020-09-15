<?php

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group events
 * @group factories
 */
class EventFactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function default_event_is_pending()
    {
        $event = Event::factory()->create();

        $this->assertEquals(EventStatus::UNSCHEDULED, $event->status);
    }

    /** @test */
    public function an_unscheduled_event_has_no_date()
    {
        $event = Event::factory()->unscheduled()->create();

        $this->assertEquals(EventStatus::UNSCHEDULED, $event->status);
        $this->assertNull($event->date);
    }

    /** @test */
    public function a_past_event_has_a_date_in_the_past()
    {
        $event = Event::factory()->past()->create();

        $this->assertEquals(EventStatus::PAST, $event->status);
        $this->assertTrue($event->date->isPast());
    }

    /** @test */
    public function a_scheduled_event_has_a_date_in_the_future()
    {
        $event = Event::factory()->scheduled()->create();

        $this->assertEquals(EventStatus::SCHEDULED, $event->status);
        $this->assertTrue($event->date->isFuture());
    }
}
