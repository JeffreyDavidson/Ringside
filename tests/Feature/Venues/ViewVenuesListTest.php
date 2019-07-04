<?php

namespace Tests\Feature\Venues;

use Tests\TestCase;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** @group venues */
class ViewVenuesListTest extends TestCase
{
    use RefreshDatabase;

    /** @var \Illuminate\Support\Collection */
    protected $venues;

    protected function setUp(): void
    {
        parent::setUp();
        $mapToIdAndName = function (Venue $venue) {
            return ['id' => $venue->id, 'name' => e($venue->name)];
        };

        $this->venues = factory(Venue::class, 3)->create()->map($mapToIdAndName);
    }

    /** @test */
    public function a_basic_user_cannot_view_venues_page()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('venues.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_venues_page()
    {
        $response = $this->get(route('venues.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_super_administrator_can_view_venues_page()
    {
        $this->actAs('super-administrator');

        $response = $this->get(route('venues.index'));

        $response->assertOk();
        $response->assertViewIs('venues.index');
    }

    /** @test */
    public function an_administrator_can_view_venues_page()
    {
        $this->actAs('administrator');

        $response = $this->get(route('venues.index'));

        $response->assertOk();
        $response->assertViewIs('venues.index');
    }

    /** @test */
    public function a_super_administrator_can_view_all_venues()
    {
        $this->actAs('super-administrator');
        // dd($this->venues->first());
        // $this->venues->first()->update(['name' => 'Test name with \\"&\' symbols']);

        $responseAjax = $this->ajaxJson(route('venues.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->venues->count(),
            'data'         => $this->venues->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_all_venues()
    {
        $this->actAs('administrator');
        // $this->venues->first()->update(['name' => 'Test name with \\"&\' symbols']);

        $responseAjax = $this->ajaxJson(route('venues.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->venues->count(),
            'data'         => $this->venues->toArray(),
        ]);
    }
}
