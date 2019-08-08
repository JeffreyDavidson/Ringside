<?php

namespace Tests\Feature\SuperAdmin\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group superadmins
 */
class ViewStableBioPageSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_view_a_stable_profile()
    {
        $this->actAs('super-administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->get(route('roster.stables.show', $stable));

        $response->assertViewIs('stables.show');
        $this->assertTrue($response->data('stable')->is($stable));
    }
}