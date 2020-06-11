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
class InjureWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_injure_a_bookable_wrestler()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->bookable()->create();

        $response = $this->injureRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertEquals(now()->toDateTimeString(), $wrestler->fresh()->currentInjury->started_at);
    }

    /** @test */
    public function an_already_injured_wrestler_cannot_be_injured()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeInjuredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->injured()->create();

        $response = $this->injureRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_wrestler_cannot_be_injured()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeInjuredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->retired()->create();

        $response = $this->injureRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_employment_wrestler_cannot_be_injured()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeInjuredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->pendingEmployment()->create();

        $response = $this->injureRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_wrestler_cannot_be_injured()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeInjuredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->suspended()->create();

        $response = $this->injureRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_injure_a_wrestler()
    {
        $wrestler = WrestlerFactory::new()->create();

        $response = $this->injureRequest($wrestler);

        $response->assertRedirect(route('login'));
    }
}
