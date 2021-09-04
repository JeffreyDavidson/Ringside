<?php

namespace Tests\Feature\Http\Controllers\Venues;

use App\Enums\Role;
use App\Http\Controllers\Venues\VenuesController;
use App\Http\Requests\Venues\StoreRequest;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\VenueRequestDataFactory;
use Tests\TestCase;

/**
 * @group venues
 * @group feature-venues
 */
class VenuesControllerStoreMethodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function create_returns_a_view()
    {
        $this
            ->actAs(Role::ADMINISTRATOR)
            ->get(action([VenuesController::class, 'create']))
            ->assertViewIs('venues.create')
            ->assertViewHas('venue', new Venue);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_creating_a_venue()
    {
        $this
            ->actAs(Role::BASIC)
            ->get(action([VenuesController::class, 'create']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_creating_a_venue()
    {
        $this
            ->get(action([VenuesController::class, 'create']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function store_creates_a_venue_and_redirects()
    {
        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([VenuesController::class, 'create']))
            ->post(action([VenuesController::class, 'store']), VenueRequestDataFactory::new()->create([
                'name' => 'Example Venue',
                'address1' => '123 Main Street',
                'address2' => 'Suite 100',
                'city' => 'Laraville',
                'state' => 'New York',
                'zip' => '12345',
            ]))
            ->assertRedirect(action([VenuesController::class, 'index']));

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
        $this
            ->actAs(Role::BASIC)
            ->from(action([VenuesController::class, 'create']))
            ->post(action([VenuesController::class, 'store']), VenueRequestDataFactory::new()->create())
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_create_a_venue()
    {
        $this
            ->from(action([VenuesController::class, 'create']))
            ->post(action([VenuesController::class, 'store']), VenueRequestDataFactory::new()->create())
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(VenuesController::class, 'store', StoreRequest::class);
    }
}
