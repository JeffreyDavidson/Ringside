<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Http\Requests\Wrestlers\StoreRequest;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\WrestlerRequestDataFactory;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group roster
 * @group feature-roster
 */
class WrestlerControllerStoreMethodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function create_returns_a_view()
    {
        $this
            ->actAs(Role::ADMINISTRATOR)
            ->get(action([WrestlersController::class, 'create']))
            ->assertViewIs('wrestlers.create')
            ->assertViewHas('wrestler', new Wrestler);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_creating_a_wrestler()
    {
        $this
            ->actAs(Role::BASIC)
            ->get(action([WrestlersController::class, 'create']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_creating_a_wrestler()
    {
        $this
            ->get(action([WrestlersController::class, 'create']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function store_creates_a_wrestler_and_redirects()
    {
        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([WrestlersController::class, 'create']))
            ->post(action([WrestlersController::class, 'store']), WrestlerRequestDataFactory::new()->create([
                'name' => 'Example Wrestler Name',
                'feet' => 6,
                'inches' => 4,
                'hometown' => 'Laraville, FL',
                'signature_move' => null,
            ]))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap(Wrestler::first(), function ($wrestler) {
            $this->assertEquals('Example Wrestler Name', $wrestler->name);
            $this->assertEquals(76, $wrestler->height->height);
            $this->assertEquals(240, $wrestler->weight);
            $this->assertEquals('Laraville, FL', $wrestler->hometown);
            $this->assertNull($wrestler->signature_move);
        });
    }

    /**
     * @test
     */
    public function signature_move_for_a_wrestler_if_signature_move_is_filled_in_request()
    {
        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([WrestlersController::class, 'create']))
            ->post(
                action([WrestlersController::class, 'index']),
                WrestlerRequestDataFactory::new()->create(['signature_move' => 'The Signature Move'])
            );

        tap(Wrestler::first(), function ($wrestler) {
            $this->assertEquals('The Signature Move', $wrestler->signature_move);
        });
    }

    /**
     * @test
     */
    public function an_employment_is_not_created_for_the_wrestler_if_started_at_is_filled_in_request()
    {
        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([WrestlersController::class, 'create']))
            ->post(
                action([WrestlersController::class, 'store']),
                WrestlerRequestDataFactory::new()->create(['started_at' => null])
            );

        tap(Wrestler::first(), function ($wrestler) {
            $this->assertCount(0, $wrestler->employments);
        });
    }

    /**
     * @test
     */
    public function an_employment_is_created_for_the_wrestler_if_started_at_is_filled_in_request()
    {
        $startedAt = now()->toDateTimeString();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([WrestlersController::class, 'create']))
            ->post(
                action([WrestlersController::class, 'store']),
                WrestlerRequestDataFactory::new()->create(['started_at' => $startedAt])
            );

        tap(Wrestler::all()->last(), function ($wrestler) use ($startedAt) {
            $this->assertCount(1, $wrestler->employments);
            $this->assertEquals($startedAt, $wrestler->employments->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_create_a_wrestler()
    {
        $this
            ->actAs(Role::BASIC)
            ->from(action([WrestlersController::class, 'create']))
            ->post(action([WrestlersController::class, 'store']), WrestlerRequestDataFactory::new()->create())
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_create_a_wrestler()
    {
        $this
            ->from(action([WrestlersController::class, 'create']))
            ->post(action([WrestlersController::class, 'store']), WrestlerRequestDataFactory::new()->create())
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(WrestlersController::class, 'store', StoreRequest::class);
    }
}
