<?php

namespace Tests\Feature\SuperAdmin\Referees;

use Tests\TestCase;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group superadmins
 */
class UnretireRefereeSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_unretire_a_retired_referee()
    {
        $this->actAs('super-administrator');
        $referee = factory(Referee::class)->states('retired')->create();

        $response = $this->put(route('referees.unretire', $referee));

        $response->assertRedirect(route('referees.index'));
        $this->assertEquals(now()->toDateTimeString(), $referee->fresh()->retirements()->latest()->first()->ended_at);
    }
}
