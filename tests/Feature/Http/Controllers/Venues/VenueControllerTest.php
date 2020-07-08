<?php

namespace Tests\Feature\Http\Controllers\Venues;

use App\Enums\Role;
use App\Models\Venue;
use App\Http\Controllers\Venues\VenuesController;
use App\Http\Requests\Venues\StoreRequest;
use App\Http\Requests\Venues\UpdateRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\VenueFactory;

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
        $this->actAs($administrators);

        $response = $this->indexRequest('venues');

        $response->assertOk();
        $response->assertViewIs('venues.index');
    }

    /** @test */
    public function a_basic_user_cannot_view_venues_index_page()
    {
        $this->actAs(Role::BASIC);

        $this->indexRequest('venues')->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_venues_index_page()
    {
        $this->indexRequest('venues')->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function create_returns_a_view($administrators)
    {
        $this->actAs($administrators);

        $response = $this->createRequest('venues');

        $response->assertViewIs('venues.create');
        $response->assertViewHas('venue', new Venue);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function store_creates_a_venue_and_redirects($administrators)
    {
        $this->actAs($administrators);

        $response = $this->storeRequest('venues', $this->validParams());

        $response->assertRedirect(route('venues.index'));
        tap(Venue::first(), function ($venue) {
            $this->assertEquals('Example Venue', $venue->name);
            $this->assertEquals('123 Main Street', $venue->address1);
            $this->assertEquals('Suite 100', $venue->address2);
            $this->assertEquals('Laraville', $venue->city);
            $this->assertEquals('New York', $venue->state);
            $this->assertEquals(12345, $venue->zip);
        });
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_venue()
    {
        $this->actAs(Role::BASIC);

        $this->createRequest('venue')->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_venue()
    {
        $this->actAs(Role::BASIC);

        $this->storeRequest('venue', $this->validParams())->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_venue()
    {
        $this->createRequest('venue')->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_create_a_venue()
    {
        $this->storeRequest('venue', $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            VenuesController::class,
            'store',
            StoreRequest::class
        );
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function show_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $venue = VenueFactory::new()->create();

        $response = $this->showRequest($venue);

        $response->assertViewIs('venues.show');
        $this->assertTrue($response->data('venue')->is($venue));
    }

    /** @test */
    public function a_basic_user_cannot_view_a_venue()
    {
        $this->actAs(Role::BASIC);
        $venue = VenueFactory::new()->create();

        $this->showRequest($venue)->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_a_venue()
    {
        $venue = VenueFactory::new()->create();

        $this->showRequest($venue)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function edit_returns_a_view($administrators)
    {
        $this->actAs($administrators);
        $venue = VenueFactory::new()->create();

        $response = $this->editRequest($venue);

        $response->assertViewIs('venues.edit');
        $this->assertTrue($response->data('venue')->is($venue));
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_venue()
    {
        $this->actAs(Role::BASIC);
        $venue = VenueFactory::new()->create();

        $this->editRequest($venue)->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_venue()
    {
        $venue = VenueFactory::new()->create();

        $this->editRequest($venue)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function updates_a_venue_and_redirects($administrators)
    {
        $this->actAs($administrators);
        $venue = VenueFactory::new()->create();

        $response = $this->updateRequest($venue, $this->validParams());

        $response->assertRedirect(route('venues.index'));
        tap(Venue::first(), function ($venue) {
            $this->assertEquals('Example Venue', $venue->name);
            $this->assertEquals('123 Main Street', $venue->address1);
            $this->assertEquals('Suite 100', $venue->address2);
            $this->assertEquals('Laraville', $venue->city);
            $this->assertEquals('New York', $venue->state);
            $this->assertEquals('12345', $venue->zip);
        });
    }

    /** @test */
    public function a_basic_user_cannot_update_a_venue()
    {
        $this->actAs(Role::BASIC);
        $venue = VenueFactory::new()->create();

        $this->updateRequest($venue, $this->validParams())->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_update_a_venue()
    {
        $venue = VenueFactory::new()->create();

        $this->updateRequest($venue, $this->validParams())->assertRedirect(route('login'));
    }

    /** @test */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            VenuesController::class,
            'update',
            UpdateRequest::class
        );
    }
}
