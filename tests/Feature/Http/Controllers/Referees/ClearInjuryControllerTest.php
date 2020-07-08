<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
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
    public function invoke_marks_a_referee_as_being_recovered_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $referee = RefereeFactory::new()->injured()->create();

        $response = $this->clearInjuryRequest($referee);

        $response->assertRedirect(route('referees.index'));
        $this->assertEquals($now->toDateTimeString(), $referee->fresh()->injuries()->latest()->first()->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_mark_an_injured_referee_as_recovered()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->injured()->create();

        $this->clearInjuryRequest($referee)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_mark_an_injured_referee_as_recovered()
    {
        $referee = RefereeFactory::new()->injured()->create();

        $this->clearInjuryRequest($referee)->assertRedirect(route('login'));
    }
}
