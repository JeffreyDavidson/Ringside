<?php

namespace Tests\Feature\SuperAdmin\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group superadmins
 */
class InjureRefereeSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_injure_a_bookable_referee()
    {
        $this->actAs('super-administrator');
        $referee = factory(Referee::class)->states('bookable')->create();

        $response = $this->put(route('referees.injure', $referee));

        $response->assertRedirect(route('referees.index'));
        $this->assertEquals(now()->toDateTimeString(), $referee->fresh()->injury->started_at);
    }
}
