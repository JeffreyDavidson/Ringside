<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\RefereeFactory;

/**
 * @group referees
 * @group feature-referees
 * @group roster
 * @group feature-roster
 */
class RetireRefereeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_a_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $referee = RefereeFactory::new()->bookable()->create();

        $response = $this->retireRequest($referee);

        $response->assertRedirect(route('referees.index'));
        $this->assertEquals($now->toDateTimeString(), $referee->fresh()->currentRetirement->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->create();

        $this->retireRequest($referee)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_retire_a_referee()
    {
        $referee = RefereeFactory::new()->create();

        $this->retireRequest($referee)->assertRedirect(route('login'));
    }
}
