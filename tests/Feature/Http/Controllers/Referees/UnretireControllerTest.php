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
class UnretireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_unretires_a_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $referee = RefereeFactory::new()->retired()->create();

        $response = $this->unretireRequest($referee);

        $response->assertRedirect(route('referees.index'));
        $this->assertEquals($now->toDateTimeString(), $referee->fresh()->retirements()->latest()->first()->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->create();

        $this->unretireRequest($referee)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_referee()
    {
        $referee = RefereeFactory::new()->create();

        $this->unretireRequest($referee)->assertRedirect(route('login'));
    }
}
