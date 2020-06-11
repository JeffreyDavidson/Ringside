<?php

namespace Tests\Feature\Wrestlers;

use App\Enums\Role;
use Tests\TestCase;
use Tests\Factories\WrestlerFactory;
use App\Exceptions\CannotBeReinstatedException;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group roster
 */
class ReinstateWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_reinstate_a_suspended_wrestler()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->suspended()->create();

        $response = $this->reinstateRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertEquals(now()->toDateTimeString(), $wrestler->fresh()->suspensions()->latest()->first()->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_reinstate_a_suspended_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->suspended()->create();

        $response = $this->reinstateRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_reinstate_a_suspended_wrestler()
    {
        $wrestler = WrestlerFactory::new()->suspended()->create();

        $response = $this->reinstateRequest($wrestler);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_bookable_wrestler_cannot_be_reinstated()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeReinstatedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->bookable()->create();

        $response = $this->reinstateRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_employment_wrestler_cannot_be_reinstated()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeReinstatedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->pendingEmployment()->create();

        $response = $this->reinstateRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function an_injured_wrestler_cannot_be_reinstated()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeReinstatedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->injured()->create();

        $response = $this->reinstateRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function an_retired_wrestler_cannot_be_reinstated()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeReinstatedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->retired()->create();

        $response = $this->reinstateRequest($wrestler);

        $response->assertForbidden();
    }
}
