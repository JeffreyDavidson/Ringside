<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\WrestlerFactory;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group roster
 * @group feature-roster
 */
class RetireWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_a_wrestler_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $wrestler = WrestlerFactory::new()->bookable()->create();

        $response = $this->retireRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertEquals($now->toDateTimeString(), $wrestler->fresh()->currentRetirement->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->create();

        $this->retireRequest($wrestler)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_retire_a_wrestler()
    {
        $wrestler = WrestlerFactory::new()->create();

        $this->retireRequest($wrestler)->assertRedirect(route('login'));
    }
}
