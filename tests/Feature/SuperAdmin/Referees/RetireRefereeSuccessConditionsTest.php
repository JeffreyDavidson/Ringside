<?php

namespace Tests\Feature\SuperAdmin\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group superadmins
 */
class RetireRefereeSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_retire_a_bookable_referee()
    {
        $this->actAs('super-administrator');
        $referee = factory(Referee::class)->states('bookable')->create();

        $response = $this->put(route('referees.retire', $referee));

        $response->assertRedirect(route('referees.index'));
        $this->assertEquals(now()->toDateTimeString(), $referee->fresh()->retirement->started_at);
    }

    /** @test */
    public function a_super_administrator_can_retire_an_injured_referee()
    {
        $this->actAs('super-administrator');
        $referee = factory(Referee::class)->states('injured')->create();

        $response = $this->put(route('referees.retire', $referee));

        $response->assertRedirect(route('referees.index'));
        $this->assertEquals(now()->toDateTimeString(), $referee->fresh()->retirement->started_at);
    }

    /** @test */
    public function a_super_administrator_can_retire_a_suspended_referee()
    {
        $this->actAs('super-administrator');
        $referee = factory(Referee::class)->states('suspended')->create();

        $response = $this->put(route('referees.retire', $referee));

        $response->assertRedirect(route('referees.index'));
        $this->assertEquals(now()->toDateTimeString(), $referee->fresh()->retirement->started_at);
    }
}
