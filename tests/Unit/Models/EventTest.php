<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use Tests\TestCase;

class EventTest extends TestCase
{
    /**
     * @test
     */
    public function an_event_date_can_be_formatted()
    {
        $event = new Event(['date' => '2020-03-05 00:00:00']);

        $this->assertEquals('March 5, 2020', $event->present()->date);
    }
}
