<?php

namespace Tests\Feature\Venues;

use Tests\TestCase;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewVenueTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_a_venue()
    {
        $this->actAs('administrator');
        $venue = factory(Venue::class)->create();

        $response = $this->get(route('venues.show', ['venue' => $venue]));

        $response->assertViewIs('venues.show');
        $this->assertTrue($response->data('venue')->is($venue));
    }

    /** @test */
    public function a_basic_user_cannot_view_a_venue()
    {
        $this->actAs('basic-user');
        $venue = factory(Venue::class)->create();

        $response = $this->get(route('venues.show', ['venue' => $venue]));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_a_venue()
    {
        $venue = factory(Venue::class)->create();

        $response = $this->get(route('venues.show', ['venue' => $venue]));

        $response->assertRedirect('/login');
    }
}
