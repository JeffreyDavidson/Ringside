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
class ReinstateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_reinstates_a_wrestler_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $wrestler = WrestlerFactory::new()->suspended()->create();

        $response = $this->reinstateRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertEquals($now->toDateTimeString(), $wrestler->fresh()->suspensions()->latest()->first()->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_reinstate_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->create();

        $this->reinstateRequest($wrestler)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_reinstate_a_wrestler()
    {
        $wrestler = WrestlerFactory::new()->create();

        $this->reinstateRequest($wrestler)->assertRedirect(route('login'));
    }
}
