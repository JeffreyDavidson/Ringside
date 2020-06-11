<?php

namespace Tests\Feature\Venues;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\VenueFactory;
use Tests\TestCase;

/**
 * @group venues
 */
class ViewVenuesListTest extends TestCase
{
    use RefreshDatabase;

    /** @var \Illuminate\Support\Collection */
    protected $venues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->venues = VenueFactory::new()->count(3)->create();
    }

    /** @test */
    public function an_super_administrator_can_view_venues_page()
    {
        $this->actAs(Role::SUPER_ADMINISTRATOR);

        $response = $this->indexRequest('venues');

        $response->assertOk();
        $response->assertViewIs('venues.index');
    }

    /** @test */
    public function an_administrator_can_view_venues_page()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->indexRequest('venues');

        $response->assertOk();
        $response->assertViewIs('venues.index');
    }

    /** @test */
    public function a_super_administrator_can_view_all_venues()
    {
        $this->actAs(Role::SUPER_ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('venues.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->venues->count(),
            'data'         => $this->venues->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_all_venues()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('venues.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->venues->count(),
            'data'         => $this->venues->toArray(),
        ]);
    }

    /** @test */
    public function a_basic_user_cannot_view_venues_page()
    {
        $this->actAs(Role::BASIC);

        $response = $this->indexRequest('venues');

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_venues_page()
    {
        $response = $this->indexRequest('venues');

        $response->assertRedirect(route('login'));
    }
}
