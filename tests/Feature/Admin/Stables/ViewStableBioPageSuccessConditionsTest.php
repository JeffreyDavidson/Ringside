<?php

namespace Tests\Feature\Admin\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group admins
 */
class ViewStableBioPageSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_a_stable_profile()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->get(route('stables.show', $stable));

        $response->assertViewIs('stables.show');
        $this->assertTrue($response->data('stable')->is($stable));
    }
}
