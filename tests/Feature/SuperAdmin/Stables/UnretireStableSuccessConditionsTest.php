<?php

namespace Tests\Feature\SuperAdmin\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group superadmins
 */
class UnretireStableSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_unretire_a_retired_stable()
    {
        $this->actAs('super-administrator');
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->put(route('roster.stables.unretire', $stable));

        $response->assertRedirect(route('roster.stables.index'));
        $this->assertEquals(now()->toDateTimeString(), $stable->fresh()->retirements()->latest()->first()->ended_at);
    }
}
