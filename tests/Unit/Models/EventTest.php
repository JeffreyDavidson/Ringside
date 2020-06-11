<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use Tests\TestCase;

/**
 * @group event
 */
class EventTest extends TestCase
{
    /** @test */
    public function a_event_has_a_name()
    {
        $event = new Event(['name' => 'Example Event Name']);

        $this->assertEquals('Example Event Name', $event->name);
    }

    /** @test */
    public function a_event_has_a_date()
    {
        $event = new Event(['date' => '2020-03-05 00:00:00']);

        $this->assertEquals('2020-03-05 00:00:00', $event->date);
    }

    /** @test */
    public function an_event_date_can_be_formatted()
    {
        $event = new Event(['date' => '2020-03-05 00:00:00']);

        $this->assertEquals('March 5, 2020', $event->formatted_date);
    }
}
