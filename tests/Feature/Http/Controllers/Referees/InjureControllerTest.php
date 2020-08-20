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
class InjureControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_injures_a_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $referee = RefereeFactory::new()->bookable()->create();

        $response = $this->injureRequest($referee);

        $response->assertRedirect(route('referees.index'));
        $this->assertEquals($now->toDateTimeString(), $referee->fresh()->currentInjury->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_injure_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->withFutureEmployment()->create();

        $this->employRequest($referee)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_injure_a_referee()
    {
        $referee = RefereeFactory::new()->create();

        $this->injureRequest($referee)->assertRedirect(route('login'));
    }
}
