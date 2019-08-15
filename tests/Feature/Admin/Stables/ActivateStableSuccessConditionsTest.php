<?php

namespace Tests\Feature\Admin\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group admins
 */
class ActivateStableSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_activate_a_pending_introduction_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('pending-introduction')->create();

        $response = $this->put(route('stables.activate', $stable));

        $response->assertRedirect(route('stables.index'));
        tap($stable->fresh(), function ($stable) {
            $this->assertTrue($stable->is_bookable);
        });
    }
}
