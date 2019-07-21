<?php

namespace Tests\Feature\SuperAdmin\Referees;

use Tests\TestCase;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group superadmins
 */
class RecoverRefereeSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_recover_an_injured_referee()
    {
        $this->actAs('super-administrator');
        $referee = factory(Referee::class)->states('injured')->create();

        $response = $this->put(route('referees.recover', $referee));

        $response->assertRedirect(route('referees.index'));
        $this->assertEquals(now()->toDateTimeString(), $referee->fresh()->injuries()->latest()->first()->ended_at);
    }
}
