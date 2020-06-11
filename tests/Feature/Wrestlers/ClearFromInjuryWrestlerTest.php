<?php

namespace Tests\Feature\Wrestlers;

use Carbon\Carbon;
use App\Enums\Role;
use Tests\TestCase;
use Tests\Factories\WrestlerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Exceptions\CannotBeClearedFromInjuryException;

/**
 * @group wrestlers
 * @group roster
 */
class ClearFromInjuryWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_recover_an_injured_wrestler()
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->injured()->create();

        $response = $this->clearInjuryRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertEquals($now->toDateTimeString(), $wrestler->fresh()->injuries()->latest()->first()->ended_at);
    }

     /** @test */
    public function a_basic_user_cannot_recover_an_injured_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->injured()->create();

        $response = $this->clearInjuryRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_recover_an_injured_wrestler()
    {
        $wrestler = WrestlerFactory::new()->injured()->create();

        $response = $this->clearInjuryRequest($wrestler);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_bookable_wrestler_cannot_be_recovered()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->bookable()->create();

        $response = $this->clearInjuryRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_employment_wrestler_cannot_be_recovered()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->pendingEmployment()->create();

        $response = $this->clearInjuryRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_wrestler_cannot_be_recovered()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->suspended()->create();

        $response = $this->clearInjuryRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function an_retired_wrestler_cannot_be_recovered()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->retired()->create();

        $response = $this->clearInjuryRequest($wrestler);

        $response->assertForbidden();
    }
}
