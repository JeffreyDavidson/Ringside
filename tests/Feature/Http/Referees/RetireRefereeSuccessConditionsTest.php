<?php

namespace Tests\Feature\Referees;

use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 */
class RetireRefereeSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_retire_a_bookable_referee($adminRoles)
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $this->actAs($adminRoles);
        $referee = RefereeFactory::new()->bookable()->create();

        $response = $this->retireRequest($referee);

        $response->assertRedirect(route('referees.index'));
        $this->assertEquals($now->toDateTimeString(), $referee->fresh()->currentRetirement->started_at);
    }

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_retire_an_injured_referee($adminRoles)
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $this->actAs($adminRoles);
        $referee = RefereeFactory::new()->injured()->create();

        $response = $this->retireRequest($referee);

        $response->assertRedirect(route('referees.index'));
        $this->assertEquals($now->toDateTimeString(), $referee->fresh()->currentRetirement->started_at);
    }

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_retire_a_suspended_referee($adminRoles)
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $this->actAs($adminRoles);
        $referee = RefereeFactory::new()->suspended()->create();

        $response = $this->retireRequest($referee);

        $response->assertRedirect(route('referees.index'));
        $this->assertEquals($now->toDateTimeString(), $referee->fresh()->currentRetirement->started_at);
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }
}
