<?php

namespace Tests\Feature\Wrestlers;

use App\Enums\Role;
use Tests\TestCase;
use Tests\Factories\WrestlerFactory;
use App\Exceptions\CannotBeRetiredException;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group roster
 */
class RetireWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_retire_a_bookable_wrestler()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->bookable()->create();

        $response = $this->retireRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertEquals(now()->toDateTimeString(), $wrestler->fresh()->currentRetirement->started_at);
    }

    /** @test */
    public function an_administrator_can_retire_a_suspended_wrestler()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->suspended()->create();

        $response = $this->retireRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertEquals(now()->toDateTimeString(), $wrestler->fresh()->currentRetirement->started_at);
    }

    /** @test */
    public function an_administrator_can_retire_an_injured_wrestler()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->injured()->create();

        $response = $this->retireRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertEquals(now()->toDateTimeString(), $wrestler->fresh()->currentRetirement->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->create();

        $response = $this->retireRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_retire_a_wrestler()
    {
        $wrestler = WrestlerFactory::new()->create();

        $response = $this->retireRequest($wrestler);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_already_retired_wrestler_cannot_be_retired()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeRetiredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->retired()->create();

        $response = $this->retireRequest($wrestler);

        $response->assertForbidden();
    }
}
