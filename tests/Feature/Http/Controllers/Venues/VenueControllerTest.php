<?php

namespace Tests\Feature\Http\Controllers\Venues;

use App\Enums\Role;
use App\Http\Controllers\Venues\VenuesController;
use App\Http\Requests\Venues\StoreRequest;
use App\Http\Requests\Venues\UpdateRequest;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group venues
 * @group feature-venues
 */
class VenueControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid Parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'name' => 'Example Venue',
            'address1' => '123 Main Street',
            'address2' => 'Suite 100',
            'city' => 'Laraville',
            'state' => 'New York',
            'zip' => '12345',
        ], $overrides);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function index_returns_a_view($administrators)
    {
        $this->actAs($administrators)
            ->get(route('venues.index'))
            ->assertOk()
            ->assertViewIs('venues.index');
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_venues_index_page()
    {
        $this->actAs(Role::BASIC)
            ->get(route('venues.index'))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_venues_index_page()
    {
        $this->get(route('venues.index'))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function create_returns_a_view($administrators)
    {
        $this->actAs($administrators)
            ->get(route('venues.create'))
            ->assertViewIs('venues.create')
            ->assertViewHas('venue', new Venue);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_creating_a_venue()
    {
        $this->actAs(Role::BASIC)
            ->get(route('venues.create'))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_creating_a_venue()
    {
        $this->get(route('venues.create'))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function store_creates_a_venue_and_redirects($administrators)
    {
        $this->actAs($administrators)
            ->from(route('venues.create'))
            ->post(route('venues.store'), $this->validParams())
            ->assertRedirect(route('venues.index'));

        tap(Venue::first(), function ($venue) {
            $this->assertEquals('Example Venue', $venue->name);
            $this->assertEquals('123 Main Street', $venue->address1);
            $this->assertEquals('Suite 100', $venue->address2);
            $this->assertEquals('Laraville', $venue->city);
            $this->assertEquals('New York', $venue->state);
            $this->assertEquals(12345, $venue->zip);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_create_a_venue()
    {
        $this->actAs(Role::BASIC)
            ->from(route('venues.create'))
            ->post(route('venues.store'), $this->validParams())
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_create_a_venue()
    {
        $this->from(route('venues.create'))
            ->post(route('venues.store'), $this->validParams())
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(VenuesController::class, 'store', StoreRequest::class);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function show_returns_a_view($administrators)
    {
        $venue = Venue::factory()->create();

        $this->actAs($administrators)
            ->get(route('venues.show', $venue))
            ->assertViewIs('venues.show')
            ->assertViewHas('venue', $venue);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_a_venue()
    {
        $venue = Venue::factory()->create();

        $this->actAs(Role::BASIC)
            ->get(route('venues.show', $venue))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_a_venue()
    {
        $venue = Venue::factory()->create();

        $this->get(route('venues.show', $venue))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function edit_returns_a_view($administrators)
    {
        $venue = Venue::factory()->create();

        $this->actAs($administrators)
            ->get(route('venues.edit', $venue))
            ->assertViewIs('venues.edit')
            ->assertViewHas('venue', $venue);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_editing_a_venue()
    {
        $venue = Venue::factory()->create();

        $this->actAs(Role::BASIC)
            ->get(route('venues.edit', $venue))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_editing_a_venue()
    {
        $venue = Venue::factory()->create();

        $this->get(route('venues.edit', $venue))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function updates_a_venue_and_redirects($administrators)
    {
        $venue = Venue::factory()->create();

        $this->actAs($administrators)
            ->from(route('venues.edit', $venue))
            ->put(route('venues.update', $venue), $this->validParams())
            ->assertRedirect(route('venues.index'));

        tap(Venue::first(), function ($venue) {
            $this->assertEquals('Example Venue', $venue->name);
            $this->assertEquals('123 Main Street', $venue->address1);
            $this->assertEquals('Suite 100', $venue->address2);
            $this->assertEquals('Laraville', $venue->city);
            $this->assertEquals('New York', $venue->state);
            $this->assertEquals('12345', $venue->zip);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_update_a_venue()
    {
        $venue = Venue::factory()->create();

        $this->actAs(Role::BASIC)
            ->from(route('venues.edit', $venue))
            ->put(route('venues.update', $venue), $this->validParams())
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_update_a_venue()
    {
        $venue = Venue::factory()->create();

        $this->from(route('venues.edit', $venue))
            ->put(route('venues.update', $venue), $this->validParams())
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(VenuesController::class, 'update', UpdateRequest::class);
    }
}
