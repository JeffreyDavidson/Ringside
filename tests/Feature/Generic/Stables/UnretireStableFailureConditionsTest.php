<?php

namespace Tests\Feature\Generic\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group generics
 */
class UnretireStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_stable_cannot_be_unretired()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->put(route('stables.unretire', $stable));

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_introduction_stable_cannot_be_unretired()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('pending-introduction')->create();

        $response = $this->put(route('stables.unretire', $stable));

        $response->assertForbidden();
    }
}
