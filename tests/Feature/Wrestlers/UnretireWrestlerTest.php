<?php

namespace Tests\Feature\Wrestlers;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\WrestlerFactory;

/**
 * @group wrestlers
 * @group roster
 */
class UnretireWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_administrator_can_unretire_a_retired_wrestler()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->retired()->create();

        $response = $this->unretireRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertEquals(now()->toDateTimeString(), $wrestler->fresh()->retirements()->latest()->first()->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->retired()->create();

        $response = $this->unretireRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_retired_wrestler()
    {
        $wrestler = WrestlerFactory::new()->retired()->create();

        $response = $this->unretireRequest($wrestler);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_basic_user_cannot_suspend_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->create();

        $response = $this->suspendRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_suspend_a_wrestler()
    {
        $wrestler = WrestlerFactory::new()->create();

        $response = $this->suspendRequest($wrestler);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_already_suspendeded_wrestler_cannot_be_suspended()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeSuspendedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->suspended()->create();

        $response = $this->suspendRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_wrestler_cannot_be_suspended()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeSuspendedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->retired()->create();

        $response = $this->suspendRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_employment_wrestler_cannot_be_suspended()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeSuspendedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->pendingEmployment()->create();

        $response = $this->suspendRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function an_injured_wrestler_cannot_be_suspended()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeSuspendedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->injured()->create();

        $response = $this->suspendRequest($wrestler);

        $response->assertForbidden();
    }
}
