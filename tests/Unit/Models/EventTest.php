<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group event
 * @group models
 */
class EventTest extends TestCase
{
    use RefreshDatabase;

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
        $event = Event::factory()->create();

        $this->assertInstanceOf(Venue::class, $event->venue);
    }

    /**
     * @test
     */
    public function an_event_date_can_be_formatted()
    {
        $event = Event::factory()->create(['date' => '2020-03-05 00:00:00']);

        $this->assertEquals('March 5, 2020', $event->formatted_date);
    }
}
