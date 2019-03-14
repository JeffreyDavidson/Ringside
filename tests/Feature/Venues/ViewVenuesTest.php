<?php

namespace Tests\Feature\Venues;

use Tests\TestCase;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewVenuesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_venues()
    {
        $this->actAs('administrator');
        $venues = factory(Venue::class, 3)->create();

        $response = $this->get(route('venues.index'));

        $response->assertOk();
        $response->assertViewIs('venues.index');
        $response->assertSee(e($venues[0]->name));
        $response->assertSee(e($venues[1]->name));
        $response->assertSee(e($venues[2]->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_venues()
    {
        $this->actAs('basic-user');
        $venues = factory(Venue::class, 3)->create();

        $response = $this->get(route('venues.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_venues()
    {
        $venues = factory(Venue::class, 3)->create();

        $response = $this->get(route('venues.index'));

        $response->assertRedirect('/login');
    }
}
