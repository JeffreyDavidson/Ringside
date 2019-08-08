<?php

namespace Tests\Feature\Admin\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group admins
 */
class RetireStableSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_retire_a_bookable_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->put(route('roster.stables.retire', $stable));

        $response->assertRedirect(route('roster.stables.index'));
        $this->assertEquals(now()->toDateTimeString(), $stable->fresh()->retirement->started_at);
    }
}
