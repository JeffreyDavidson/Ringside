<?php

namespace Tests\Integration\Http\Livewire\Events;

use App\Http\Livewire\Events\EventsList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group events
 * @group integration-events
 */
class EventsListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_should_return_correct_view()
    {
        Livewire::test(EventsList::class)
            ->assertViewIs('livewire.events.events-list');
    }

    /**
     * @test
     */
    public function it_should_pass_correct_data()
    {
        Livewire::test(EventsList::class)
            ->assertViewHas('events');
    }
}
