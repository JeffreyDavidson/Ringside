<?php

namespace Tests\Feature\Generics\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group generics
 */
class ViewStableBioPageSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_stables_name_can_be_seen_on_their_profile()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create(['name' => 'Example Stable Name']);

        $response = $this->get(route('roster.stables.show', $stable));

        $response->assertSee('Example Stable Name');
    }
}
