<?php

use App\Enums\Role;
use App\Models\Venue;
use Tests\Factories\VenueFactory;
use Tests\TestCase;

/**
 * @group venues
 * @group feature-venues
 */
class VenueControllerTest extends TestCase
{
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

    /** @test */
    public function an_administrator_can_view_the_form_for_creating_a_venue()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->createRequest('venues');

        $response->assertViewIs('venues.create');
    }

    /** @test */
    public function an_administrator_can_create_a_venue()
    {
        $this->actAs(Role::ADMINISTRATOR);

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

        $response = $this->createRequest('venue');

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_venue()
    {
        $this->actAs(Role::BASIC);

        $response = $this->storeRequest('venue', $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_venue()
    {
        $response = $this->createRequest('venue');

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_create_a_venue()
    {
        $response = $this->storeRequest('venue', $this->validParams());

        $response->assertRedirect(route('login'));
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

    /** @test */
    public function an_administrator_can_view_a_venue()
    {
        $this->actAs(Role::ADMINISTRATOR);
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

        $response = $this->showRequest($venue);

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_a_venue()
    {
        $venue = VenueFactory::new()->create();

        $response = $this->showRequest($venue);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_editing_a_venue()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $venue = VenueFactory::new()->create();

        $response = $this->editRequest($venue);

        $response->assertViewIs('venues.edit');
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_venue()
    {
        $this->actAs(Role::BASIC);
        $venue = VenueFactory::new()->create();

        $response = $this->editRequest($venue);

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_venue()
    {
        $venue = VenueFactory::new()->create();

        $response = $this->editRequest($venue);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_administrator_can_update_a_venue()
    {
        $this->actAs(Role::ADMINISTRATOR);
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

        $response = $this->updateRequest($venue, $this->validParams());

        $response->assertStatus(403);
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
