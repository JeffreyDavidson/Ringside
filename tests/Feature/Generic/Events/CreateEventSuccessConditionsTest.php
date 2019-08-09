<?php

namespace Tests\Feature\Generics\Events;

use Tests\TestCase;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group events
 * @group generics
 */
class CreateEventSuccessConditionsTest extends TestCase
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
    public function an_event_date_is_optional()
    {
        $this->actAs('administrator');

        $response = $this->post(route('events.store'), $this->validParams([
            'date' => ''
        ]));

        $response->assertSessionDoesntHaveErrors('date');
    }

    /** @test */
    public function an_event_venue_id_is_optional()
    {
        $this->actAs('administrator');

        $response = $this->post(route('events.store'), $this->validParams([
            'venue_id' => ''
        ]));

        $response->assertSessionDoesntHaveErrors('venue_id');
    }

    /** @test */
    public function an_event_preview_is_optional()
    {
        $this->actAs('administrator');

        $response = $this->post(route('events.store'), $this->validParams([
            'preview' => ''
        ]));

        $response->assertSessionDoesntHaveErrors('preview');
    }
}
