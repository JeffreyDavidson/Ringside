<?php

namespace Tests\Feature\Admin\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group admins
 */
class UnretireStableSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_unretire_a_retired_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('retired')->create();

        $response = $this->put(route('stables.unretire', $stable));

        $response->assertRedirect(route('stables.index'));
        $this->assertEquals(now()->toDateTimeString(), $stable->fresh()->retirements()->latest()->first()->ended_at);
    }
}
