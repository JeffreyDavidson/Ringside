<?php

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\Role;
use App\Http\Controllers\Events\EventsController;
use App\Models\Event;
use App\Models\Venue;
use Carbon\Carbon;
use Tests\Factories\EventRequestDataFactory;
use Tests\TestCase;

/**
 * @group events
 * @group feature-events
 */
class EventControllerUpdateMethodTest extends TestCase
{
    /**
     * @test
     */
    public function edit_displays_correct_view_with_data()
    {
        $event = Event::factory()->scheduled()->create();

        $this
            ->actAs(Role::administrator())
            ->get(action([EventsController::class, 'edit'], $event))
            ->assertViewIs('events.edit')
            ->assertViewHas('event', $event);
    }

    /**
     * @test
     */
    public function an_administrator_can_view_the_form_for_editing_a_scheduled_event()
    {
        $event = Event::factory()->scheduled()->create();

        $this
            ->actAs(Role::administrator())
            ->get(action([EventsController::class, 'edit'], $event))
            ->assertSuccessful();
    }

    /**
     * @test
     */
    public function an_administrator_can_view_the_form_for_editing_an_unscheduled_event()
    {
        $event = Event::factory()->unscheduled()->create();

        $this
            ->actAs(Role::administrator())
            ->get(action([EventsController::class, 'edit'], $event))
            ->assertSuccessful();
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_editing_an_event()
    {
        $event = Event::factory()->create();

        $this
            ->actAs(Role::basic())
            ->get(action([EventsController::class, 'edit'], $event))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_editing_an_event()
    {
        $event = Event::factory()->unscheduled()->create();

        $this
            ->get(action([EventsController::class, 'edit'], $event))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function a_past_event_cannot_be_edited()
    {
        $event = Event::factory()->past()->create();

        $this
            ->actAs(Role::administrator())
            ->get(action([EventsController::class, 'edit'], $event))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function an_administrator_can_update_a_scheduled_event()
    {
        $venue = Venue::factory()->create();
        $newVenue = Venue::factory()->create();
        $oldDate = Carbon::parse('+2 weeks');
        $newDate = Carbon::parse('+1 weeks');

        $event = Event::factory()
            ->for($venue)
            ->scheduledOn($oldDate->toDateTimeString())
            ->withName('Old Name')
            ->withPreview('This old preview')
            ->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([EventsController::class, 'edit'], $event))
            ->put(
                action([EventsController::class, 'update'], $event),
                EventRequestDataFactory::new()->withEvent($event)->create([
                    'name' => 'Example Event Name',
                    'date' => $newDate->toDateTimeString(),
                    'venue_id' => $newVenue->id,
                    'preview' => 'This is an new event preview.',
                ])
            )
            ->assertRedirect(action([EventsController::class, 'index']));

        tap($event->fresh(), function ($event) use ($newVenue, $newDate) {
            $this->assertEquals('Example Event Name', $event->name);
            $this->assertEquals($newDate->toDateTimeString(), $event->date->toDateTimeString());
            $this->assertTrue($event->venue->is($newVenue));
            $this->assertEquals('This is an new event preview.', $event->preview);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_update_an_event()
    {
        $event = Event::factory()->create();

        $this
            ->actAs(Role::basic())
            ->from(action([EventsController::class, 'edit'], $event))
            ->put(
                action([EventsController::class, 'update'], $event),
                EventRequestDataFactory::new()->withEvent($event)->create()
            )
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_update_an_event()
    {
        $event = Event::factory()->create();

        $this
            ->from(action([EventsController::class, 'edit'], $event))
            ->put(
                action([EventsController::class, 'update'], $event),
                EventRequestDataFactory::new()->withEvent($event)->create()
            )
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function an_administrator_cannot_update_a_past_event()
    {
        $event = Event::factory()->past()->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([EventsController::class, 'edit'], $event))
            ->put(
                action([EventsController::class, 'update'], $event),
                EventRequestDataFactory::new()->withEvent($event)->create()
            )
            ->assertForbidden();
    }
}
