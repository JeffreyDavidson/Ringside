<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\WrestlerFactory;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group roster
 * @group feature-roster
 */
class ClearInjuryControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_marks_a_wrestler_as_being_recovered_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $wrestler = WrestlerFactory::new()->injured()->create();

        $response = $this->clearInjuryRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertEquals($now->toDateTimeString(), $wrestler->fresh()->injuries()->latest()->first()->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_mark_an_injured_wrestler_as_recovered()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->injured()->create();

        $this->clearInjuryRequest($wrestler)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_mark_an_injured_wrestler_as_recovered()
    {
        $wrestler = WrestlerFactory::new()->injured()->create();

        $this->clearInjuryRequest($wrestler)->assertRedirect(route('login'));
    }
}
