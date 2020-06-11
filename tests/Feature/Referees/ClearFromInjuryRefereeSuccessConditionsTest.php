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
class ClearFromInjuryRefereeSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_clear_an_injured_referee($adminRoles)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($adminRoles);
        $referee = RefereeFactory::new()->injured()->create();

        $response = $this->clearInjuryRequest($referee);

        $response->assertRedirect(route('referees.index'));
        $this->assertEquals($now->toDateTimeString(), $referee->fresh()->injuries()->latest()->first()->ended_at);
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }
}
