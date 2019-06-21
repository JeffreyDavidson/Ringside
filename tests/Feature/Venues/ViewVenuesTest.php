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
        $venues->first()->update(['name' => 'Test name with \\"&\' symbols']);

        $response = $this->get(route('venues.index'));
        $responseAjax = $this->getJson(route('venues.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk();
        $response->assertViewIs('venues.index');
        $responseAjax->assertJson([
            'recordsTotal' => $venues->count(),
            'data'         => $venues->map(function (Venue $venue) {
                return ['id' => $venue->id, 'name' => e($venue->name)];
            })->toArray(),
        ]);
    }

    /** @test */
    public function a_basic_user_cannot_view_all_venues()
    {
        $this->actAs('basic-user');
        factory(Venue::class, 3)->create();

        $response = $this->get(route('venues.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_venues()
    {
        factory(Venue::class, 3)->create();

        $response = $this->get(route('venues.index'));

        $response->assertRedirect(route('login'));
    }
}
