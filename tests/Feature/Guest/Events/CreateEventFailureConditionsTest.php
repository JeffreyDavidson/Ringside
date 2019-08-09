<?php

namespace Tests\Feature\Guest\Events;

use Tests\TestCase;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group guests
 */
class CreateEventFailureConditionsTest extends TestCase
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
            'venue_id' => factory(Venue::class)->create()->id,
            'preview' => 'This is an event preview.',
        ], $overrides);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_an_event()
    {
        $response = $this->get(route('events.create'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_create_an_event()
    {
        $response = $this->post(route('events.store'), $this->validParams());

        $response->assertRedirect(route('login'));
    }
}
