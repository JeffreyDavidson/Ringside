<?php

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\Role;
use App\Http\Controllers\Events\EventsController;
use App\Http\Requests\Events\UpdateRequest;
use App\Models\Event;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\EventRequestDataFactory;
use Tests\TestCase;

/**
 * @group events
 * @group feature-events
 */
class EventControllerUpdateMethodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function edit_displays_correct_view_with_data()
    {
        $event = Event::factory()->scheduled()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
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
            ->actAs(Role::ADMINISTRATOR)
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
            ->actAs(Role::ADMINISTRATOR)
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
            ->actAs(Role::BASIC)
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
            ->actAs(Role::ADMINISTRATOR)
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
            ->actAs(Role::ADMINISTRATOR)
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
            ->actAs(Role::BASIC)
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
            ->actAs(Role::ADMINISTRATOR)
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
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(EventsController::class, 'update', UpdateRequest::class);
    }
}
