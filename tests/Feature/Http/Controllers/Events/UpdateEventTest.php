<?php

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\Role;
use App\Http\Controllers\Events\EventsController;
use App\Http\Requests\Events\UpdateRequest;
use App\Models\Event;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group events
 * @group feature-events
 */
class UpdateEventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid parameters for request.
     *
     * @param  array  $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'name' => 'Example Event Name',
            'date' => now()->toDateTimeString(),
            'venue_id' => Venue::factory()->create()->id,
            'preview' => 'This is an event preview.',
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_editing_a_scheduled_event()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = Event::factory()->scheduled()->create();

        $response = $this->get(route('events.edit', $event));

        $response->assertViewIs('events.edit');
        $this->assertTrue($response->data('event')->is($event));
    }

    /** @test */
    public function an_administrator_can_update_a_scheduled_event()
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);
        $event = Event::factory()->scheduled()->create();

        $response = $this->patch(route('events.update', $event), $this->validParams());

        $response->assertRedirect(route('events.index'));
        tap($event->fresh(), function ($event) use ($now) {
            $this->assertEquals('Example Event Name', $event->name);
            $this->assertEquals($now->toDateTimeString(), $event->date);
            $this->assertEquals('This is an event preview.', $event->preview);
        });
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_an_event()
    {
        $this->actAs(Role::BASIC);
        $event = Event::factory()->create();

        $response = $this->editRequest($event);

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_an_event()
    {
        $this->actAs(Role::BASIC);
        $event = Event::factory()->create();

        $response = $this->updateRequest($event, $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_an_event()
    {
        $event = Event::factory()->create();

        $response = $this->get(route('events.edit', $event));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_update_an_event()
    {
        $event = Event::factory()->create();

        $response = $this->patch(route('events.update', $event), $this->validParams());

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_past_event_cannot_be_edited()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = Event::factory()->past()->create();

        $response = $this->get(route('events.edit', $event));

        $response->assertForbidden();
    }

    /** @test */
    public function a_past_event_cannot_be_updated()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $event = Event::factory()->past()->create();

        $response = $this->patch(route('events.update', $event), $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            EventsController::class,
            'update',
            UpdateRequest::class
        );
    }
}
